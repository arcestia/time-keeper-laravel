<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Expedition;
use App\Models\UserExpedition;
use App\Models\UserStats;
use App\Models\UserTimeWallet;
use App\Models\TimeAccount;
use App\Models\StoreItem;
use App\Models\UserStorageItem;
use App\Services\PremiumService;
use App\Services\ProgressService;
use Flasher\Laravel\Facade\Flasher;

class ExpeditionController extends Controller
{
    public function page()
    {
        return view('expeditions.index');
    }

    public function catalog(): JsonResponse
    {
        $level = (int) request('level', 0);
        $q = Expedition::query();
        if ($level >= 1 && $level <= 5) { $q->where('level', $level); }
        $list = $q->orderBy('level')->orderBy('id')->limit(200)->get();
        return response()->json($list);
    }

    public function my(): JsonResponse
    {
        $userId = Auth::id();
        $rows = UserExpedition::with('expedition')->where('user_id',$userId)->orderByDesc('id')->limit(200)->get();
        return response()->json($rows);
    }

    public function buy(int $expeditionId): JsonResponse
    {
        $user = Auth::user();
        $exp = Expedition::findOrFail($expeditionId);
        $now = now();
        $source = request()->input('source','wallet');
        if (!in_array($source,['wallet','bank'],true)) $source = 'wallet';

        $result = DB::transaction(function() use($user,$exp,$source,$now){
            // charge cost from time balances
            $price = (int)$exp->cost_seconds;
            $wallet = null; $bank = null;
            if ($source==='wallet'){
                $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$wallet || (int)$wallet->available_seconds < $price){
                    Flasher::addError('Not enough wallet balance'); abort(422,'Not enough wallet balance');
                }
                $wallet->available_seconds = (int)$wallet->available_seconds - $price; $wallet->save();
            } else {
                $bank = TimeAccount::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$bank || (int)$bank->base_balance_seconds < $price){
                    Flasher::addError('Not enough bank balance'); abort(422,'Not enough bank balance');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $price; $bank->save();
            }
            // choose a randomized duration
            $dur = random_int((int)$exp->min_duration_seconds, (int)$exp->max_duration_seconds);
            // base xp: 1 per 30s
            $baseXp = max(1, (int) floor($dur / 30));
            $ue = UserExpedition::create([
                'user_id' => $user->id,
                'expedition_id' => $exp->id,
                'status' => 'pending',
                'purchased_at' => $now,
                'duration_seconds' => $dur,
                'base_xp' => $baseXp,
            ]);
            return [$ue,$wallet,$bank];
        });
        [$ue,$wallet,$bank] = $result;
        Flasher::addSuccess('Purchased expedition: '.$exp->name);
        return response()->json(['ok'=>true,'expedition'=>$ue]);
    }

    public function buyLevel(): JsonResponse
    {
        $user = Auth::user();
        $level = (int) request()->input('level', 0);
        if ($level < 1 || $level > 5) { abort(422, 'Invalid level'); }
        $exp = Expedition::where('level',$level)->inRandomOrder()->firstOrFail();
        $now = now();
        $source = request()->input('source','wallet');
        if (!in_array($source,['wallet','bank'],true)) $source = 'wallet';

        $result = DB::transaction(function() use($user,$exp,$source,$now){
            $price = (int)$exp->cost_seconds;
            $wallet = null; $bank = null;
            if ($source==='wallet'){
                $wallet = UserTimeWallet::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$wallet || (int)$wallet->available_seconds < $price){
                    Flasher::addError('Not enough wallet balance'); abort(422,'Not enough wallet balance');
                }
                $wallet->available_seconds = (int)$wallet->available_seconds - $price; $wallet->save();
            } else {
                $bank = TimeAccount::where('user_id',$user->id)->lockForUpdate()->first();
                if (!$bank || (int)$bank->base_balance_seconds < $price){
                    Flasher::addError('Not enough bank balance'); abort(422,'Not enough bank balance');
                }
                $bank->base_balance_seconds = (int)$bank->base_balance_seconds - $price; $bank->save();
            }
            $dur = random_int((int)$exp->min_duration_seconds, (int)$exp->max_duration_seconds);
            $baseXp = max(1, (int) floor($dur / 30));
            $ue = UserExpedition::create([
                'user_id' => $user->id,
                'expedition_id' => $exp->id,
                'status' => 'pending',
                'purchased_at' => $now,
                'duration_seconds' => $dur,
                'base_xp' => $baseXp,
            ]);
            return [$ue,$wallet,$bank,$exp];
        });
        [$ue,$wallet,$bank,$exp] = $result;
        Flasher::addSuccess('Purchased expedition (random L'.$exp->level.'): '.$exp->name);
        return response()->json(['ok'=>true,'expedition'=>$ue]);
    }

    public function start(int $userExpeditionId): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        return DB::transaction(function() use($user,$userExpeditionId,$now){
            // enforce one active at a time
            $active = UserExpedition::where(['user_id'=>$user->id,'status'=>'active'])->lockForUpdate()->first();
            if ($active) { abort(422,'You already have an active expedition'); }
            $ue = UserExpedition::where(['id'=>$userExpeditionId,'user_id'=>$user->id])->lockForUpdate()->firstOrFail();
            if ($ue->status !== 'pending') { abort(422,'Expedition is not pending'); }
            $exp = Expedition::findOrFail($ue->expedition_id);
            $ue->status = 'active';
            $ue->started_at = $now;
            $ue->ends_at = $now->copy()->addSeconds((int)$ue->duration_seconds);
            $ue->save();
            // energy cost on start
            $stats = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
            if (!$stats) { $stats = UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
            $prem = PremiumService::getOrCreate($user->id);
            $capMult = 1.0; if (PremiumService::isActive($prem)) { $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated); $capMult = (float) (PremiumService::benefitsForTier($tier)['cap_multiplier'] ?? 1.0); }
            $cap = (int) floor(100 * $capMult);
            $stats->energy = max(0, (int)$stats->energy - (int)$exp->energy_cost_pct);
            $stats->energy = min($cap, (int)$stats->energy);
            $stats->save();
            return response()->json(['ok'=>true,'expedition'=>$ue]);
        });
    }

    public function claim(int $userExpeditionId): JsonResponse
    {
        $user = Auth::user();
        $now = now();
        return DB::transaction(function() use($user,$userExpeditionId,$now){
            $ue = UserExpedition::where(['id'=>$userExpeditionId,'user_id'=>$user->id])->lockForUpdate()->firstOrFail();
            if ($ue->status !== 'active') { abort(422,'Expedition is not active'); }
            if (!$ue->ends_at || $now->lt($ue->ends_at)) { abort(422,'Expedition not finished yet'); }
            $ue->status = 'completed';
            $ue->save();
            // apply XP with premium multiplier
            $xp = (int) $ue->base_xp;
            $prem = PremiumService::getOrCreate($user->id);
            if (PremiumService::isActive($prem)) {
                $tier = PremiumService::tierFor((int)$prem->premium_seconds_accumulated);
                $benefits = PremiumService::benefitsForTier($tier);
                $mult = (float)($benefits['xp_multiplier'] ?? 1.0);
                if ($mult > 1.0) { $xp = max(1, (int) floor($xp * $mult)); }
            }
            app(ProgressService::class)->addXp($user->id, $xp);
            // deplete food/water based on duration: 1% per hour rounded up
            $hours = max(1, (int) ceil(((int)$ue->duration_seconds)/3600));
            $deplete = min(100, $hours);
            $stats = UserStats::where('user_id',$user->id)->lockForUpdate()->first();
            if (!$stats) { $stats = UserStats::create(['user_id'=>$user->id,'energy'=>100,'food'=>100,'water'=>100,'leisure'=>100,'health'=>100]); }
            $stats->food = max(0, (int)$stats->food - $deplete);
            $stats->water = max(0, (int)$stats->water - $deplete);
            $stats->save();
            // generate loot: 1-3 random items with small quantities; deliver to storage
            $loot = [];
            $items = StoreItem::where('is_active', true)->inRandomOrder()->limit(5)->get();
            $count = random_int(1, min(3, max(1, $items->count())));
            for ($i=0; $i<$count; $i++) {
                $si = $items[$i]; if (!$si) break;
                $q = random_int(1, 5);
                $loot[] = ['key'=>$si->key,'name'=>$si->name,'qty'=>$q];
                $sto = UserStorageItem::where(['user_id'=>$user->id,'store_item_id'=>$si->id])->lockForUpdate()->first();
                if (!$sto) { $sto = UserStorageItem::create(['user_id'=>$user->id,'store_item_id'=>$si->id,'quantity'=>0]); }
                $sto->quantity = (int)$sto->quantity + $q; $sto->save();
            }
            $ue->loot = $loot; $ue->status = 'claimed'; $ue->save();
            Flasher::addSuccess('Expedition claimed: +'.$xp.' XP and loot');
            return response()->json(['ok'=>true,'xp'=>$xp,'loot'=>$loot]);
        });
    }
}
