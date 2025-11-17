<?php
namespace App\Jobs;

use App\Models\TokenExchangeOrder;
use App\Models\TokenExchangeTrade;
use App\Models\TimeAccount;
use App\Models\UserTimeToken;
use App\Models\TimeKeeperReserve;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenExchangeMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;
    public float $takerFee = 0.002; // 0.2%

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $maxIterations = 200;
        while ($maxIterations-- > 0) {
            $done = DB::transaction(function(){
                $order = TokenExchangeOrder::lockForUpdate()->find($this->orderId);
                if (!$order) return true; // nothing
                if (!in_array($order->status, ['open','partial'], true)) return true;
                $remaining = max(0, (int)$order->qty_total - (int)$order->qty_filled);
                if ($remaining <= 0) { $order->status = 'filled'; $order->save(); return true; }

                // Find best counter orders
                if ($order->side === 'buy') {
                    $counter = TokenExchangeOrder::open()
                        ->where('color',$order->color)
                        ->where('side','sell')
                        ->where('price_per_unit_seconds','<=',$order->price_per_unit_seconds)
                        ->orderBy('price_per_unit_seconds')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();
                } else {
                    $counter = TokenExchangeOrder::open()
                        ->where('color',$order->color)
                        ->where('side','buy')
                        ->where('price_per_unit_seconds','>=',$order->price_per_unit_seconds)
                        ->orderByDesc('price_per_unit_seconds')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();
                }
                if (!$counter) return true; // no match now

                $price = $counter->price_per_unit_seconds; // pay maker price
                $maker = $counter; $taker = $order;
                // Compute fill qty
                $availTaker = max(0, (int)$taker->qty_total - (int)$taker->qty_filled);
                $availMaker = max(0, (int)$maker->qty_total - (int)$maker->qty_filled);
                $fillQty = min($availTaker, $availMaker);
                if ($fillQty <= 0) return true;

                $notional = (int) ($price * $fillQty);
                $fee = (int) floor($notional * $this->takerFee);

                // Settlement
                if ($taker->side === 'buy') {
                    // Buyer is taker: buyer already held exact notional seconds on taker order.
                    // Reserve fee from buyer's payment: seller receives notional - fee; reserve gets fee.
                    // Credit seller seconds
                    $sellerId = $maker->user_id; $buyerId = $taker->user_id; $color = $taker->color;
                    $acctSeller = TimeAccount::query()->where('user_id',$sellerId)->lockForUpdate()->first();
                    if (!$acctSeller) { $acctSeller = TimeAccount::create(['user_id'=>$sellerId,'base_balance_seconds'=>0]); }
                    $acctSeller->base_balance_seconds = (int)$acctSeller->base_balance_seconds + max(0, $notional - $fee); $acctSeller->save();
                    // Credit reserve
                    $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
                    if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
                    $reserve->balance_seconds = (int)$reserve->balance_seconds + $fee; $reserve->save();
                    // Deduct buyer held seconds
                    $taker->held_seconds = max(0, (int)$taker->held_seconds - $notional);
                    // Transfer tokens: debit maker held tokens, credit buyer tokens
                    $maker->held_tokens = max(0, (int)$maker->held_tokens - $fillQty);
                    $dst = UserTimeToken::query()->where(['user_id'=>$buyerId,'color'=>$color])->lockForUpdate()->first();
                    if (!$dst) { $dst = UserTimeToken::create(['user_id'=>$buyerId,'color'=>$color,'quantity'=>0]); }
                    $dst->quantity = (int)$dst->quantity + $fillQty; $dst->save();
                } else {
                    // Seller is taker: seller already held tokens on taker order; maker buyer pays notional.
                    // Apply taker fee by skimming from seller's proceeds.
                    $sellerId = $taker->user_id; $buyerId = $maker->user_id; $color = $taker->color;
                    $acctSeller = TimeAccount::query()->where('user_id',$sellerId)->lockForUpdate()->first();
                    if (!$acctSeller) { $acctSeller = TimeAccount::create(['user_id'=>$sellerId,'base_balance_seconds'=>0]); }
                    $acctSeller->base_balance_seconds = (int)$acctSeller->base_balance_seconds + max(0, $notional - $fee); $acctSeller->save();
                    $reserve = TimeKeeperReserve::query()->lockForUpdate()->first();
                    if (!$reserve) { $reserve = TimeKeeperReserve::create(['balance_seconds' => 0]); }
                    $reserve->balance_seconds = (int)$reserve->balance_seconds + $fee; $reserve->save();
                    // Maker buyer consumes held seconds
                    $maker->held_seconds = max(0, (int)$maker->held_seconds - $notional);
                    // Transfer tokens: debit taker (seller) held tokens, credit buyer tokens
                    $taker->held_tokens = max(0, (int)$taker->held_tokens - $fillQty);
                    $dst = UserTimeToken::query()->where(['user_id'=>$buyerId,'color'=>$color])->lockForUpdate()->first();
                    if (!$dst) { $dst = UserTimeToken::create(['user_id'=>$buyerId,'color'=>$color,'quantity'=>0]); }
                    $dst->quantity = (int)$dst->quantity + $fillQty; $dst->save();
                }

                // Update orders fill
                $taker->qty_filled = (int)$taker->qty_filled + $fillQty;
                $maker->qty_filled = (int)$maker->qty_filled + $fillQty;
                $taker->status = ($taker->qty_filled >= $taker->qty_total) ? 'filled' : 'partial';
                $maker->status = ($maker->qty_filled >= $maker->qty_total) ? 'filled' : 'partial';
                $taker->save(); $maker->save();

                // Create trade
                TokenExchangeTrade::create([
                    'buy_order_id' => $taker->side==='buy' ? $taker->id : $maker->id,
                    'sell_order_id' => $taker->side==='sell' ? $taker->id : $maker->id,
                    'color' => $order->color,
                    'price_per_unit_seconds' => $price,
                    'qty' => $fillQty,
                    'taker_user_id' => $taker->user_id,
                    'maker_user_id' => $maker->user_id,
                    'fee_seconds' => $fee,
                ]);

                return false; // continue loop for more fills
            });
            if ($done) break;
        }
    }
}
