<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeLine;
use App\Models\User;
use App\Models\UserInventoryItem;
use App\Models\UserStorageItem;
use App\Models\UserTimeWallet;
use App\Models\TimeAccount;
use App\Models\TimeKeeperReserve;
use App\Models\StoreItem;
use App\Services\TimeTokenService;
use App\Services\TimeBankService;
use App\Support\TimeUnits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TradeController extends Controller
{
    public function page(): View
    {
        return view('trades.index');
    }

    public function myTokens(TimeTokenService $tokens): JsonResponse
    {
        $user = Auth::user();
        $balances = $tokens->getBalances($user->id);
        return response()->json(['ok' => true, 'balances' => $balances]);
    }

    public function myItems(): JsonResponse
    {
        $user = Auth::user();
        $invRows = UserInventoryItem::query()->where('user_id', $user->id)->get(['store_item_id','quantity']);
        $stRows = UserStorageItem::query()->where('user_id', $user->id)->get(['store_item_id','quantity']);
        $ids = collect($invRows)->pluck('store_item_id')->merge(collect($stRows)->pluck('store_item_id'))->unique()->values()->all();
        $names = StoreItem::query()->whereIn('id', $ids)->pluck('name','id');
        $inv = collect($invRows)->map(function($r) use ($names){ $id=(int)$r->store_item_id; return ['item_id'=>$id,'name'=>(string)($names[$id] ?? ('Item #'.$id)),'qty'=>(int)$r->quantity]; })->values();
        $st = collect($stRows)->map(function($r) use ($names){ $id=(int)$r->store_item_id; return ['item_id'=>$id,'name'=>(string)($names[$id] ?? ('Item #'.$id)),'qty'=>(int)$r->quantity]; })->values();
        return response()->json(['ok'=>true,'inventory'=>$inv,'storage'=>$st]);
    }

    public function pageShow($id): View
    {
        $user = Auth::user();
        $trade = Trade::findOrFail($id);
        if ($trade->user_a_id !== $user->id && $trade->user_b_id !== $user->id) {
            abort(403);
        }
        if ($trade->status === 'canceled') {
            abort(404);
        }
        return view('trades.show', ['tradeId' => $trade->id]);
    }

    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $trade = Trade::with('lines')->findOrFail($id);
        if ($trade->user_a_id !== $user->id && $trade->user_b_id !== $user->id) {
            abort(403);
        }
        if ($trade->expires_at && now()->greaterThan($trade->expires_at) && $trade->status === 'open') {
            $trade->status = 'canceled';
            $trade->canceled_by = $trade->user_a_id;
            $trade->canceled_at = now();
            $trade->save();
        }
        $partnerId = $trade->user_a_id === $user->id ? $trade->user_b_id : $trade->user_a_id;
        $partner = User::find($partnerId);
        // Build item name map for any item lines
        $ids = collect($trade->lines ?? [])->filter(function($l){ return in_array($l->type, ['item_inventory','item_storage']); })
            ->map(function($l){ return (int)($l->payload['item_id'] ?? 0); })->filter()->unique()->values();
        $itemNames = $ids->count() ? StoreItem::query()->whereIn('id',$ids)->pluck('name','id') : collect();
        return response()->json([
            'ok' => true,
            'trade' => $trade,
            'partner' => $partner ? ['id'=>$partner->id,'username'=>$partner->username,'name'=>$partner->name] : null,
            'item_names' => $itemNames,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = Auth::user();
        $q = Trade::query()
            ->where(function($s) use ($user){ $s->where('user_a_id',$user->id)->orWhere('user_b_id',$user->id); })
            ->orderByDesc('updated_at');
        $trades = $q->limit(50)->get();
        $out = $trades->map(function(Trade $t) use ($user){
            $partnerId = $t->user_a_id === $user->id ? $t->user_b_id : $t->user_a_id;
            $p = User::find($partnerId);
            return [
                'id' => $t->id,
                'status' => $t->status,
                'a_accepted' => (bool)$t->a_accepted,
                'b_accepted' => (bool)$t->b_accepted,
                'expires_at' => optional($t->expires_at)->toDateTimeString(),
                'updated_at' => $t->updated_at->toDateTimeString(),
                'partner' => $p ? ['id'=>$p->id,'username'=>$p->username,'name'=>$p->name] : null,
            ];
        })->values();
        return response()->json(['ok'=>true,'trades'=>$out]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'partner_username' => ['required','string'],
        ]);
        $user = Auth::user();
        $partner = User::where('username', $data['partner_username'])->first();
        if (!$partner || $partner->id === $user->id) {
            return response()->json(['ok'=>false,'message'=>'Invalid partner'], 422);
        }
        $trade = Trade::create([
            'user_a_id' => $user->id,
            'user_b_id' => $partner->id,
            'status' => 'open',
            'a_accepted' => false,
            'b_accepted' => false,
            'expires_at' => now()->addDays(7),
        ]);
        return response()->json(['ok'=>true,'id'=>$trade->id]);
    }

    private function sideFor(Trade $trade, int $userId): ?string
    {
        if ($trade->user_a_id === $userId) return 'a';
        if ($trade->user_b_id === $userId) return 'b';
        return null;
    }

    public function addLine($id, Request $request, TimeTokenService $tokens): JsonResponse
    {
        $trade = Trade::lockForUpdate()->findOrFail($id);
        $user = Auth::user();
        $side = $this->sideFor($trade, $user->id);
        if (!$side || $trade->status !== 'open') abort(403);
        $data = $request->validate([
            'type' => ['required','string','in:item_inventory,item_storage,time_token,time_balance'],
            'payload' => ['required','array'],
        ]);
        // Determine existing quantity for merge
        $existingQty = 0; $mergeKey = null; $mergeType = $data['type'];
        $linesSameSide = TradeLine::where('trade_id', $trade->id)->where('side', $side)->get();
        if ($data['type'] === 'time_token') {
            $c = strtolower((string)($data['payload']['color'] ?? ''));
            $qty = (int)($data['payload']['qty'] ?? 0);
            if (!in_array($c, ['red','blue','green','yellow','black'], true) || $qty < 1) return response()->json(['ok'=>false,'message'=>'Invalid token'],422);
            $mergeKey = $c;
            foreach ($linesSameSide as $l) {
                if ($l->type === 'time_token' && strtolower((string)($l->payload['color'] ?? '')) === $c) {
                    $existingQty += (int)($l->payload['qty'] ?? 0);
                }
            }
            $bal = $tokens->getBalances($user->id);
            if (($bal[$c] ?? 0) < ($existingQty + $qty)) return response()->json(['ok'=>false,'message'=>'Insufficient tokens for total quantity'],422);
        } elseif ($data['type'] === 'time_balance') {
            $src = ($data['payload']['source'] ?? 'bank');
            $amountStr = (string)($data['payload']['amount'] ?? '');
            $seconds = TimeUnits::parseToSeconds($amountStr) ?? 0;
            if (!in_array($src, ['bank','wallet'], true) || $seconds <= 0) return response()->json(['ok'=>false,'message'=>'Invalid amount or source'],422);
            if ($src === 'bank') {
                $acc = TimeAccount::firstOrCreate(['user_id'=>$user->id],[ 'base_balance_seconds'=>0 ]);
                if ((int)$acc->base_balance_seconds < $seconds) return response()->json(['ok'=>false,'message'=>'Insufficient bank balance'],422);
            } else {
                $wal = UserTimeWallet::firstOrCreate(['user_id'=>$user->id],[ 'available_seconds'=>0 ]);
                if ((int)$wal->available_seconds < $seconds) return response()->json(['ok'=>false,'message'=>'Insufficient wallet balance'],422);
            }
        } elseif ($data['type'] === 'item_inventory') {
            $itemId = (int)($data['payload']['item_id'] ?? 0);
            $qty = (int)($data['payload']['qty'] ?? 0);
            if ($itemId<=0 || $qty<1) return response()->json(['ok'=>false,'message'=>'Invalid item'],422);
            $mergeKey = $itemId;
            foreach ($linesSameSide as $l) {
                if ($l->type === 'item_inventory' && (int)($l->payload['item_id'] ?? 0) === $itemId) {
                    $existingQty += (int)($l->payload['qty'] ?? 0);
                }
            }
            $have = (int) (UserInventoryItem::where(['user_id'=>$user->id,'store_item_id'=>$itemId])->value('quantity') ?? 0);
            if ($have < ($existingQty + $qty)) return response()->json(['ok'=>false,'message'=>'Insufficient inventory qty for total'],422);
        } elseif ($data['type'] === 'item_storage') {
            $itemId = (int)($data['payload']['item_id'] ?? 0);
            $qty = (int)($data['payload']['qty'] ?? 0);
            if ($itemId<=0 || $qty<1) return response()->json(['ok'=>false,'message'=>'Invalid item'],422);
            $mergeKey = $itemId;
            foreach ($linesSameSide as $l) {
                if ($l->type === 'item_storage' && (int)($l->payload['item_id'] ?? 0) === $itemId) {
                    $existingQty += (int)($l->payload['qty'] ?? 0);
                }
            }
            $have = (int) (UserStorageItem::where(['user_id'=>$user->id,'store_item_id'=>$itemId])->value('quantity') ?? 0);
            if ($have < ($existingQty + $qty)) return response()->json(['ok'=>false,'message'=>'Insufficient storage qty for total'],422);
        }
        // Merge into existing line when possible (items and tokens)
        $line = null;
        if (in_array($mergeType, ['item_inventory','item_storage','time_token'], true) && $mergeKey !== null) {
            foreach ($linesSameSide as $l) {
                if (
                    ($mergeType==='time_token' && $l->type==='time_token' && strtolower((string)($l->payload['color'] ?? '')) === (string)$mergeKey)
                    || ($mergeType==='item_inventory' && $l->type==='item_inventory' && (int)($l->payload['item_id'] ?? 0) === (int)$mergeKey)
                    || ($mergeType==='item_storage' && $l->type==='item_storage' && (int)($l->payload['item_id'] ?? 0) === (int)$mergeKey)
                ) {
                    $p = $l->payload; $p['qty'] = (int)($p['qty'] ?? 0) + (int)($data['payload']['qty'] ?? 0);
                    $l->payload = $p; $l->save(); $line = $l; break;
                }
            }
        }
        if (!$line) {
            $line = TradeLine::create([
                'trade_id' => $trade->id,
                'side' => $side,
                'type' => $data['type'],
                'payload' => $data['payload'],
            ]);
        }
        $trade->a_accepted = false;
        $trade->b_accepted = false;
        $trade->save();
        return response()->json(['ok'=>true,'line'=>$line]);
    }

    public function removeLine($id, Request $request): JsonResponse
    {
        $trade = Trade::lockForUpdate()->findOrFail($id);
        $user = Auth::user();
        $side = $this->sideFor($trade, $user->id);
        if (!$side || $trade->status !== 'open') abort(403);
        $data = $request->validate([
            'line_id' => ['required','integer'],
        ]);
        $line = TradeLine::where('trade_id',$trade->id)->where('id',$data['line_id'])->firstOrFail();
        $line->delete();
        $trade->a_accepted = false;
        $trade->b_accepted = false;
        $trade->save();
        return response()->json(['ok'=>true]);
    }

    public function accept($id): JsonResponse
    {
        $trade = Trade::lockForUpdate()->findOrFail($id);
        $user = Auth::user();
        $side = $this->sideFor($trade, $user->id);
        if (!$side || $trade->status !== 'open') abort(403);
        if ($side === 'a') $trade->a_accepted = true; else $trade->b_accepted = true;
        $trade->save();
        // Auto finalize when both accepted
        if ($trade->a_accepted && $trade->b_accepted) {
            $this->performFinalize($trade);
        }
        return response()->json(['ok'=>true,'a_accepted'=>$trade->a_accepted,'b_accepted'=>$trade->b_accepted,'status'=>$trade->status]);
    }

    public function unaccept($id): JsonResponse
    {
        $trade = Trade::lockForUpdate()->findOrFail($id);
        $user = Auth::user();
        $side = $this->sideFor($trade, $user->id);
        if (!$side || $trade->status !== 'open') abort(403);
        if ($side === 'a') $trade->a_accepted = false; else $trade->b_accepted = false;
        $trade->save();
        return response()->json(['ok'=>true]);
    }

    public function cancel($id): JsonResponse
    {
        $trade = Trade::lockForUpdate()->findOrFail($id);
        $user = Auth::user();
        if ($trade->status !== 'open') abort(422);
        if ($trade->user_a_id !== $user->id && $trade->user_b_id !== $user->id) abort(403);
        $trade->status = 'canceled';
        $trade->canceled_by = $user->id;
        $trade->canceled_at = now();
        $trade->save();
        return response()->json(['ok'=>true]);
    }

    private function performFinalize(Trade $trade): void
    {
        $tokens = app(TimeTokenService::class);
        $bank = app(TimeBankService::class);
        $lines = TradeLine::where('trade_id',$trade->id)->get();
        DB::transaction(function () use ($trade, $lines, $tokens, $bank) {
            foreach (['a','b'] as $side) {
                $fromId = $side==='a' ? $trade->user_a_id : $trade->user_b_id;
                $toId = $side==='a' ? $trade->user_b_id : $trade->user_a_id;
                foreach ($lines->where('side',$side) as $line) {
                    if ($line->type === 'time_token') {
                        $c = strtolower((string)($line->payload['color'] ?? ''));
                        $qty = (int)($line->payload['qty'] ?? 0);
                        if ($qty > 0) {
                            $res = $tokens->debit($fromId, $c, $qty);
                            if (!($res['ok'] ?? false) || (int)($res['taken'] ?? 0) < $qty) abort(422, 'Insufficient tokens');
                            $tokens->credit($toId, $c, $qty);
                        }
                    } elseif ($line->type === 'time_balance') {
                        $src = ($line->payload['source'] ?? 'bank');
                        $amount = TimeUnits::parseToSeconds((string)$line->payload['amount']) ?? 0;
                        $fee = (int) ceil($amount * 0.03);
                        if ($src === 'bank') {
                            $fromAcc = TimeAccount::firstOrCreate(['user_id'=>$fromId],['base_balance_seconds'=>0]);
                            if ((int)$fromAcc->base_balance_seconds < ($amount + $fee)) abort(422,'Insufficient bank balance including fee');
                            $bank->withdraw($fromAcc->refresh(), $amount + $fee, 'trade transfer + fee');
                            $toAcc = TimeAccount::firstOrCreate(['user_id'=>$toId],['base_balance_seconds'=>0]);
                            $bank->deposit($toAcc->refresh(), $amount, 'trade received');
                            $reserve = TimeKeeperReserve::query()->lockForUpdate()->firstOrCreate([], ['balance_seconds'=>0]);
                            $reserve->balance_seconds = (int)$reserve->balance_seconds + $fee;
                            $reserve->save();
                        } else {
                            $fromWal = UserTimeWallet::query()->lockForUpdate()->firstOrCreate(['user_id'=>$fromId],['available_seconds'=>0]);
                            if ((int)$fromWal->available_seconds < ($amount + $fee)) abort(422,'Insufficient wallet balance including fee');
                            $fromWal->available_seconds = (int)$fromWal->available_seconds - ($amount + $fee);
                            $fromWal->save();
                            $toWal = UserTimeWallet::query()->lockForUpdate()->firstOrCreate(['user_id'=>$toId],['available_seconds'=>0]);
                            $toWal->available_seconds = (int)$toWal->available_seconds + $amount;
                            $toWal->save();
                            $reserve = TimeKeeperReserve::query()->lockForUpdate()->firstOrCreate([], ['balance_seconds'=>0]);
                            $reserve->balance_seconds = (int)$reserve->balance_seconds + $fee;
                            $reserve->save();
                        }
                    } elseif ($line->type === 'item_inventory') {
                        $itemId = (int)($line->payload['item_id'] ?? 0);
                        $qty = (int)($line->payload['qty'] ?? 0);
                        $inv = UserInventoryItem::query()->lockForUpdate()->where(['user_id'=>$fromId,'store_item_id'=>$itemId])->first();
                        $have = (int)($inv->quantity ?? 0);
                        if ($qty<1 || $have < $qty) abort(422,'Insufficient inventory items');
                        if ($inv) {
                            $inv->quantity = (int)$inv->quantity - $qty;
                            if ($inv->quantity <= 0) { $inv->delete(); } else { $inv->save(); }
                        }
                        $toStore = UserStorageItem::query()->lockForUpdate()->firstOrCreate(['user_id'=>$toId,'store_item_id'=>$itemId],['quantity'=>0]);
                        $toStore->quantity = (int)$toStore->quantity + $qty;
                        $toStore->save();
                    } elseif ($line->type === 'item_storage') {
                        $itemId = (int)($line->payload['item_id'] ?? 0);
                        $qty = (int)($line->payload['qty'] ?? 0);
                        $st = UserStorageItem::query()->lockForUpdate()->where(['user_id'=>$fromId,'store_item_id'=>$itemId])->first();
                        $have = (int)($st->quantity ?? 0);
                        if ($qty<1 || $have < $qty) abort(422,'Insufficient storage items');
                        if ($st) {
                            $st->quantity = (int)$st->quantity - $qty;
                            if ($st->quantity <= 0) { $st->delete(); } else { $st->save(); }
                        }
                        $toStore = UserStorageItem::query()->lockForUpdate()->firstOrCreate(['user_id'=>$toId,'store_item_id'=>$itemId],['quantity'=>0]);
                        $toStore->quantity = (int)$toStore->quantity + $qty;
                        $toStore->save();
                    }
                }
            }
            $trade->status = 'finalized';
            $trade->finalized_at = now();
            $trade->save();
        });
    }

    public function finalize($id, TimeTokenService $tokens, TimeBankService $bank): JsonResponse
    {
        $trade = Trade::lockForUpdate()->findOrFail($id);
        $user = Auth::user();
        if ($trade->status !== 'open') abort(422);
        if ($trade->user_a_id !== $user->id && $trade->user_b_id !== $user->id) abort(403);
        if (!$trade->a_accepted || !$trade->b_accepted) return response()->json(['ok'=>false,'message'=>'Both must accept'], 422);
        $this->performFinalize($trade);
        return response()->json(['ok'=>true]);
    }
}
