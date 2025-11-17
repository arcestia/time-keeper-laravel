<?php
namespace App\Http\Controllers;

use App\Models\TokenExchangeOrder;
use App\Models\TokenExchangeTrade;
use App\Models\TimeAccount;
use App\Models\UserTimeToken;
use App\Services\TimeTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\TokenExchangeMatchJob;

class ExchangeController extends Controller
{
    public function page()
    {
        return view('exchange.index');
    }

    public function place(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validate([
            'side' => ['required','in:buy,sell'],
            'color' => ['required','string','in:red,blue,green,yellow,black,diamond'],
            'price' => ['required','integer','min:1'],
            'qty' => ['required','integer','min:1'],
        ]);
        $side = $data['side']; $color = strtolower($data['color']); $price = (int)$data['price']; $qty = (int)$data['qty'];

        $order = DB::transaction(function () use ($user, $side, $color, $price, $qty, $tokens) {
            $heldSeconds = 0; $heldTokens = 0;
            if ($side === 'buy') {
                $need = (int) ($price * $qty);
                $acct = TimeAccount::query()->where('user_id',$user->id)->lockForUpdate()->first();
                if (!$acct || (int)$acct->base_balance_seconds < $need) {
                    abort(422,'Insufficient bank balance');
                }
                // lock by deducting from bank and storing on order (refunded on cancel/unfilled)
                $acct->base_balance_seconds = (int)$acct->base_balance_seconds - $need; $acct->save();
                $heldSeconds = $need;
            } else {
                // sell: lock tokens by debiting to zero-sum hold (refunded on cancel/unfilled)
                $row = UserTimeToken::query()->where(['user_id'=>$user->id,'color'=>$color])->lockForUpdate()->first();
                $have = (int)($row->quantity ?? 0);
                if ($have < $qty) { abort(422,'Not enough tokens'); }
                $row->quantity = $have - $qty; if ($row->quantity <= 0) { $row->delete(); } else { $row->save(); }
                $heldTokens = $qty;
            }
            $o = TokenExchangeOrder::create([
                'user_id' => $user->id,
                'side' => $side,
                'color' => $color,
                'price_per_unit_seconds' => $price,
                'qty_total' => $qty,
                'qty_filled' => 0,
                'status' => 'open',
                'held_seconds' => $heldSeconds,
                'held_tokens' => $heldTokens,
            ]);
            return $o;
        });

        // enqueue match
        TokenExchangeMatchJob::dispatch($order->id);
        return response()->json(['ok'=>true,'order'=>$order]);
    }

    public function cancel(int $id): JsonResponse
    {
        $user = Auth::user();
        $order = TokenExchangeOrder::findOrFail($id);
        if ($order->user_id !== $user->id) abort(403);
        if (!in_array($order->status, ['open','partial'], true)) {
            return response()->json(['ok'=>false,'message'=>'Order not open'], 422);
        }
        DB::transaction(function () use ($order) {
            $remaining = max(0, (int)$order->qty_total - (int)$order->qty_filled);
            if ($remaining > 0) {
                if ($order->side === 'buy') {
                    // refund remaining seconds proportionally
                    $per = (int)$order->price_per_unit_seconds; $want = (int)($per * $remaining);
                    $acct = TimeAccount::query()->where('user_id',$order->user_id)->lockForUpdate()->first();
                    if (!$acct) { $acct = TimeAccount::create(['user_id'=>$order->user_id,'base_balance_seconds'=>0]); }
                    $acct->base_balance_seconds = (int)$acct->base_balance_seconds + $want; $acct->save();
                    $order->held_seconds = max(0, (int)$order->held_seconds - $want);
                } else {
                    // refund remaining tokens
                    $left = $remaining;
                    $row = UserTimeToken::query()->where(['user_id'=>$order->user_id,'color'=>$order->color])->lockForUpdate()->first();
                    if (!$row) { $row = UserTimeToken::create(['user_id'=>$order->user_id,'color'=>$order->color,'quantity'=>0]); }
                    $row->quantity = (int)$row->quantity + $left; $row->save();
                    $order->held_tokens = max(0, (int)$order->held_tokens - $left);
                }
            }
            $order->status = 'canceled'; $order->save();
        });
        return response()->json(['ok'=>true]);
    }

    public function orderbook(Request $request): JsonResponse
    {
        $color = strtolower($request->query('color','red'));
        $depth = max(1, min(50, (int)$request->query('depth', 20)));
        // Aggregate top bids and asks
        $bids = TokenExchangeOrder::open()->where(['color'=>$color,'side'=>'buy'])
            ->select('price_per_unit_seconds', DB::raw('SUM(qty_total - qty_filled) as qty'))
            ->groupBy('price_per_unit_seconds')
            ->orderByDesc('price_per_unit_seconds')
            ->limit($depth)->get();
        $asks = TokenExchangeOrder::open()->where(['color'=>$color,'side'=>'sell'])
            ->select('price_per_unit_seconds', DB::raw('SUM(qty_total - qty_filled) as qty'))
            ->groupBy('price_per_unit_seconds')
            ->orderBy('price_per_unit_seconds')
            ->limit($depth)->get();
        $trades = TokenExchangeTrade::where('color',$color)->orderByDesc('id')->limit(25)->get();
        return response()->json(['ok'=>true,'bids'=>$bids,'asks'=>$asks,'trades'=>$trades]);
    }

    public function my(): JsonResponse
    {
        $uid = Auth::id();
        $orders = TokenExchangeOrder::where('user_id',$uid)->orderByDesc('id')->limit(200)->get();
        $trades = TokenExchangeTrade::where(function($q) use($uid){ $q->where('taker_user_id',$uid)->orWhere('maker_user_id',$uid); })->orderByDesc('id')->limit(200)->get();
        return response()->json(['ok'=>true,'orders'=>$orders,'trades'=>$trades]);
    }
}
