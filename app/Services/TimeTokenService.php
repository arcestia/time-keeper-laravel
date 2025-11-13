<?php
namespace App\Services;

use App\Models\TimeAccount;
use App\Models\TimeKeeperReserve;
use App\Models\UserTimeToken;
use Illuminate\Support\Facades\DB;

class TimeTokenService
{
    public function valueSeconds(string $color): int
    {
        $map = config('time_tokens.values', []);
        $key = strtolower(trim($color));
        return (int) ($map[$key] ?? 0);
    }

    public function itemKey(string $color): ?string
    {
        $map = config('time_tokens.store_item_keys', []);
        $key = strtolower(trim($color));
        return $map[$key] ?? null;
    }

    public function getBalances(int $userId): array
    {
        $rows = UserTimeToken::query()->where('user_id', $userId)->get(['color','quantity']);
        $out = ['red'=>0,'blue'=>0,'green'=>0,'yellow'=>0,'black'=>0];
        foreach ($rows as $r) {
            $c = strtolower((string)$r->color);
            if (array_key_exists($c, $out)) { $out[$c] = (int)$r->quantity; }
        }
        return $out;
    }

    public function credit(int $userId, string $color, int $qty): UserTimeToken
    {
        $c = strtolower(trim($color));
        return DB::transaction(function () use ($userId, $c, $qty) {
            $row = UserTimeToken::query()->where(['user_id'=>$userId,'color'=>$c])->lockForUpdate()->first();
            if (!$row) { $row = UserTimeToken::create(['user_id'=>$userId,'color'=>$c,'quantity'=>0]); }
            $row->quantity = (int)$row->quantity + max(0, $qty);
            $row->save();
            return $row;
        });
    }

    public function debit(int $userId, string $color, int $qty): array
    {
        $c = strtolower(trim($color));
        return DB::transaction(function () use ($userId, $c, $qty) {
            $row = UserTimeToken::query()->where(['user_id'=>$userId,'color'=>$c])->lockForUpdate()->first();
            $have = (int)($row->quantity ?? 0);
            $take = min(max(1, $qty), $have);
            if ($take <= 0) { return ['ok'=>false,'taken'=>0,'remaining'=>$have]; }
            if ($row) {
                $row->quantity = (int)$row->quantity - $take;
                if ($row->quantity <= 0) { $row->delete(); } else { $row->save(); }
            }
            return ['ok'=>true,'taken'=>$take,'remaining'=>max(0,$have-$take)];
        });
    }

    /**
     * Exchange tokens into bank balance, limited by reserve seconds.
     */
    public function exchange(int $userId, string $color, int $qty): array
    {
        $qty = max(1, (int)$qty);
        $perSeconds = $this->valueSeconds($color);
        if ($perSeconds <= 0) {
            return ['ok' => false, 'message' => 'Unknown token color'];
        }
        return DB::transaction(function () use ($userId, $color, $qty, $perSeconds) {
            $tok = UserTimeToken::query()->where(['user_id'=>$userId,'color'=>strtolower($color)])
                ->lockForUpdate()->first();
            $have = (int)($tok->quantity ?? 0);
            if ($have <= 0) { abort(422, 'No tokens available'); }
            $want = min($qty, $have);

            $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
            if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
            $reserveSeconds = (int) $reserve->balance_seconds;
            $maxByReserve = intdiv(max(0, $reserveSeconds), max(1, $perSeconds));
            $fulfill = min($want, $maxByReserve);
            if ($fulfill <= 0) { abort(422, 'Reserve has insufficient balance'); }

            // Debit tokens
            if ($tok) {
                $tok->quantity = (int)$tok->quantity - $fulfill;
                if ($tok->quantity <= 0) { $tok->delete(); } else { $tok->save(); }
            }

            // Credit user's bank and debit reserve
            $seconds = $fulfill * $perSeconds;
            $account = TimeAccount::query()->where('user_id',$userId)->lockForUpdate()->first();
            if (!$account) { $account = TimeAccount::create(['user_id'=>$userId, 'base_balance_seconds'=>0]); }
            $account->base_balance_seconds = (int)$account->base_balance_seconds + $seconds;
            $account->save();
            $reserve->balance_seconds = (int)$reserve->balance_seconds - $seconds;
            $reserve->save();

            return [
                'ok' => true,
                'exchanged_qty' => (int)$fulfill,
                'credited_seconds' => (int)$seconds,
                'remaining_qty' => (int) max(0, $have - $fulfill),
            ];
        });
    }
}
