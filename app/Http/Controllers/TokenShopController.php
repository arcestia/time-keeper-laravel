<?php
namespace App\Http\Controllers;

use App\Models\StoreItem;
use App\Models\UserExpeditionUpgrade;
use App\Models\UserStorageItem;
use App\Models\UserTimeToken;
use App\Models\UserXpBoost;
use App\Models\UserExpeditionUpgradeGrant;
use App\Services\TimeTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TokenShopController extends Controller
{
    public function page()
    {
        return view('token-shop.index');
    }

    public function balances(TimeTokenService $tokens): JsonResponse
    {
        $user = Auth::user();
        $balances = $tokens->getBalances($user->id);
        return response()->json(['ok' => true, 'balances' => $balances]);
    }

    public function buySlot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'color' => ['required', 'string', 'in:yellow,black,blue,green'],
            'qty' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $color = strtolower($data['color']);
        $qty = (int)($data['qty'] ?? 1);
        $user = Auth::user();

        $maxExtra = 250;

        $result = DB::transaction(function () use ($user, $color, $qty, $maxExtra) {
            $upgrade = UserExpeditionUpgrade::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$upgrade) {
                $upgrade = UserExpeditionUpgrade::create([
                    'user_id' => $user->id,
                    'permanent_slots' => 0,
                    'temp_slots' => 0,
                    'temp_expires_at' => null,
                ]);
            }

            $currentExtra = (int)$upgrade->permanent_slots + (int)$upgrade->temp_slots;
            if ($currentExtra + $qty > $maxExtra) {
                return ['ok' => false, 'message' => 'Extra expedition slots cap reached'];
            }

            $tok = UserTimeToken::query()
                ->where(['user_id' => $user->id, 'color' => $color])
                ->lockForUpdate()
                ->first();
            $have = (int)($tok->quantity ?? 0);
            if ($have < $qty) {
                return ['ok' => false, 'message' => 'Not enough tokens'];
            }

            $tok->quantity = $have - $qty;
            if ($tok->quantity <= 0) {
                $tok->delete();
            } else {
                $tok->save();
            }

            if ($color === 'black') {
                $upgrade->permanent_slots = (int)$upgrade->permanent_slots + $qty;
                // record grant for permanent slots
                UserExpeditionUpgradeGrant::create([
                    'user_id' => $user->id,
                    'type' => 'permanent',
                    'slots' => $qty,
                    'expires_at' => null,
                ]);
            } elseif (in_array($color, ['yellow','green','blue'], true)) {
                // All non-black colors give temporary slots; duration per token color
                $upgrade->temp_slots = (int)$upgrade->temp_slots + $qty;
                $now = now();
                $base = $upgrade->temp_expires_at && $upgrade->temp_expires_at->gt($now)
                    ? $upgrade->temp_expires_at
                    : $now;
                // Determine per-token seconds via TimeTokenService valueSeconds
                $perSeconds = app(\App\Services\TimeTokenService::class)->valueSeconds($color);
                $secondsToAdd = max(1, (int)$perSeconds) * $qty;
                $upgrade->temp_expires_at = $base->copy()->addSeconds($secondsToAdd);
                // record grant for temp slots (expiry for this purchase only)
                UserExpeditionUpgradeGrant::create([
                    'user_id' => $user->id,
                    'type' => 'temp',
                    'slots' => $qty,
                    'expires_at' => $now->copy()->addSeconds($secondsToAdd),
                ]);
            }

            $upgrade->save();

            return [
                'ok' => true,
                'permanent_slots' => (int)$upgrade->permanent_slots,
                'temp_slots' => (int)$upgrade->temp_slots,
                'temp_expires_at' => $upgrade->temp_expires_at,
            ];
        });

        if (!($result['ok'] ?? false)) {
            $msg = (string)($result['message'] ?? 'Purchase failed');
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        return response()->json($result);
    }

    public function buyXp(Request $request, TimeTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'color' => ['required', 'string', 'in:red,blue,green,yellow,black'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $user = Auth::user();
        $color = strtolower($data['color']);
        $qty = (int)$data['qty'];

        $perSeconds = $tokens->valueSeconds($color);
        if ($perSeconds <= 0) {
            return response()->json(['ok' => false, 'message' => 'Unknown token color'], 422);
        }

        $result = DB::transaction(function () use ($user, $color, $qty, $perSeconds) {
            $tok = UserTimeToken::query()
                ->where(['user_id' => $user->id, 'color' => $color])
                ->lockForUpdate()
                ->first();
            $have = (int)($tok->quantity ?? 0);
            if ($have < $qty) {
                return ['ok' => false, 'message' => 'Not enough tokens'];
            }

            $tok->quantity = $have - $qty;
            if ($tok->quantity <= 0) {
                $tok->delete();
            } else {
                $tok->save();
            }
            // Each token grants +2% XP multiplier; qty stacks additively
            $bonusAdd = 0.02 * $qty;
            if ($bonusAdd <= 0) {
                return ['ok' => false, 'message' => 'Token value too small'];
            }

            // Duration depends on token color (use valueSeconds in seconds)
            $seconds = max(1, (int)$perSeconds);
            $now = now();

            // Create a new boost record per purchase (no merging)
            $newBoost = UserXpBoost::create([
                'user_id' => $user->id,
                'bonus_percent' => $bonusAdd,
                'expires_at' => $now->copy()->addSeconds($seconds),
            ]);

            // Compute total active bonus after this purchase
            $totalActive = (float) UserXpBoost::query()
                ->where('user_id', $user->id)
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', $now)
                ->sum('bonus_percent');

            return [
                'ok' => true,
                'bonus_percent' => $totalActive,
                'expires_at' => $newBoost->expires_at,
                'added_percent' => $bonusAdd,
                'spent_qty' => $qty,
            ];
        });

        if (!($result['ok'] ?? false)) {
            $msg = (string)($result['message'] ?? 'Purchase failed');
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        return response()->json($result);
    }

    public function openChest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'color' => ['required', 'string', 'in:red,blue,green,yellow,black'],
        ]);

        $color = strtolower($data['color']);
        $user = Auth::user();

        $result = DB::transaction(function () use ($user, $color) {
            $tok = UserTimeToken::query()
                ->where(['user_id' => $user->id, 'color' => $color])
                ->lockForUpdate()
                ->first();
            $have = (int)($tok->quantity ?? 0);
            if ($have < 1) {
                return ['ok' => false, 'message' => 'Not enough tokens'];
            }

            $tok->quantity = $have - 1;
            if ($tok->quantity <= 0) {
                $tok->delete();
            } else {
                $tok->save();
            }

            $loot = [];
            if (in_array($color, ['red', 'blue', 'green'], true)) {
                $item = StoreItem::query()->where('is_active', true)->inRandomOrder()->first();
                if (!$item) {
                    return ['ok' => false, 'message' => 'No store items available'];
                }
                if ($color === 'red') {
                    $qty = random_int(200, 500);
                } elseif ($color === 'blue') {
                    $qty = random_int(1000, 5000);
                } else {
                    $qty = random_int(50000, 150000);
                }
                $loot[] = ['key' => $item->key, 'name' => $item->name, 'qty' => $qty];
                $sto = UserStorageItem::query()->where([
                    'user_id' => $user->id,
                    'store_item_id' => $item->id,
                ])->lockForUpdate()->first();
                if (!$sto) {
                    $sto = UserStorageItem::create([
                        'user_id' => $user->id,
                        'store_item_id' => $item->id,
                        'quantity' => 0,
                    ]);
                }
                $sto->quantity = (int)$sto->quantity + $qty;
                $sto->save();
            } else {
                $items = StoreItem::query()->where('is_active', true)->get();
                if ($items->isEmpty()) {
                    return ['ok' => false, 'message' => 'No store items available'];
                }
                foreach ($items as $item) {
                    if ($color === 'yellow') {
                        $qty = 100000;
                    } else {
                        $qty = 2500000;
                    }
                    $loot[] = ['key' => $item->key, 'name' => $item->name, 'qty' => $qty];
                    $sto = UserStorageItem::query()->where([
                        'user_id' => $user->id,
                        'store_item_id' => $item->id,
                    ])->lockForUpdate()->first();
                    if (!$sto) {
                        $sto = UserStorageItem::create([
                            'user_id' => $user->id,
                            'store_item_id' => $item->id,
                            'quantity' => 0,
                        ]);
                    }
                    $sto->quantity = (int)$sto->quantity + $qty;
                    $sto->save();
                }
            }

            return ['ok' => true, 'loot' => $loot];
        });

        if (!($result['ok'] ?? false)) {
            $msg = (string)($result['message'] ?? 'Open chest failed');
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        return response()->json($result);
    }

    public function boosts(): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        $rows = UserXpBoost::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $created = $r->created_at ?: $now;
            $expires = $r->expires_at;
            $totalSec = null; $remainSec = null; $progress = 0.0; $active = false;
            if ($expires) {
                $totalSec = max(1, $expires->diffInSeconds($created, false) * -1 ?: $expires->diffInSeconds($created));
                $remainSec = max(0, $expires->diffInSeconds($now, false) * -1);
                $active = $expires->gt($now);
                $progress = $totalSec > 0 ? max(0.0, min(1.0, ($totalSec - $remainSec) / $totalSec)) : 1.0;
            }
            $out[] = [
                'id' => (int)$r->id,
                'bonus_percent' => (float)$r->bonus_percent,
                'created_at' => $created,
                'expires_at' => $expires,
                'active' => $active,
                'total_seconds' => $totalSec,
                'remaining_seconds' => $remainSec,
                'progress' => $progress,
            ];
        }
        return response()->json(['ok' => true, 'boosts' => $out]);
    }

    public function slotGrants(): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        $rows = UserExpeditionUpgradeGrant::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $created = $r->created_at ?: $now;
            $expires = $r->expires_at;
            $active = $expires ? $expires->gt($now) : true; // permanent treated as active
            $totalSec = null; $remainSec = null; $progress = null;
            if ($expires) {
                $totalSec = max(1, $expires->diffInSeconds($created, false) * -1 ?: $expires->diffInSeconds($created));
                $remainSec = max(0, $expires->diffInSeconds($now, false) * -1);
                $progress = $totalSec > 0 ? max(0.0, min(1.0, ($totalSec - $remainSec) / $totalSec)) : 1.0;
            }
            $out[] = [
                'id' => (int)$r->id,
                'type' => (string)$r->type,
                'slots' => (int)$r->slots,
                'created_at' => $created,
                'expires_at' => $expires,
                'active' => $active,
                'total_seconds' => $totalSec,
                'remaining_seconds' => $remainSec,
                'progress' => $progress,
            ];
        }
        return response()->json(['ok'=>true,'grants'=>$out]);
    }

    public function slotStats(): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        $upgrade = UserExpeditionUpgrade::query()->where('user_id', $user->id)->first();
        $perm = (int)($upgrade->permanent_slots ?? 0);
        $temp = 0; $expires = null;
        if ($upgrade && $upgrade->temp_expires_at && $upgrade->temp_expires_at->gt($now)) {
            $temp = (int)($upgrade->temp_slots ?? 0);
            $expires = $upgrade->temp_expires_at;
        }
        return response()->json([
            'ok' => true,
            'permanent' => $perm,
            'temp_active' => $temp,
            'temp_expires_at' => $expires,
            'total_extra' => max(0, $perm + $temp),
        ]);
    }
}
