<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\TimeKeeperController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpeditionController;
use App\Http\Controllers\TokenShopController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\GuildController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/bank', [BankController::class, 'page'])->name('bank.page');
    Route::get('/bank/data', [BankController::class, 'index'])->name('bank.index');
    Route::get('/bank/user-time', [BankController::class, 'userTime'])->name('bank.user_time');
    Route::post('/bank/passcode', [BankController::class, 'setPasscode'])->name('bank.passcode');
    Route::post('/bank/passcode/change', [BankController::class, 'changePasscode'])->name('bank.passcode.change');
    Route::post('/bank/login', [BankController::class, 'login'])->name('bank.login');
    Route::post('/bank/lock', [BankController::class, 'lock'])->name('bank.lock');
    Route::post('/bank/deposit', [BankController::class, 'deposit'])->name('bank.deposit');
    Route::post('/bank/withdraw', [BankController::class, 'withdraw'])->name('bank.withdraw');
    Route::post('/bank/transfer', [BankController::class, 'transfer'])->name('bank.transfer');
    Route::post('/bank/exchange-tokens', [BankController::class, 'exchangeTokens'])->name('bank.exchange_tokens');
    Route::get('/bank/token-balances', [BankController::class, 'tokenBalances'])->name('bank.token_balances');

    // Player stats
    Route::get('/api/me/stats', [StatsController::class, 'me'])->name('stats.me');
    Route::patch('/api/me/stats', [StatsController::class, 'updateMe'])->name('stats.updateMe');

    // Progress (levels & XP)
    Route::get('/api/me/progress', [\App\Http\Controllers\ProgressController::class, 'me'])->name('progress.me');
    Route::post('/api/me/xp/add', [\App\Http\Controllers\ProgressController::class, 'addXp'])->name('progress.addXp');
    Route::get('/api/me/xp-boost', [\App\Http\Controllers\ProgressController::class, 'xpBoost'])->name('progress.xp_boost');

    // Admin
    Route::get('/admin', [\App\Http\Controllers\AdminController::class, 'page'])->name('admin.page');
    Route::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'usersSearch'])->name('admin.users.search');
    Route::get('/admin/users/{id}/stats', [\App\Http\Controllers\AdminController::class, 'getUserStats'])->name('admin.users.stats');
    Route::patch('/admin/users/{id}/stats', [\App\Http\Controllers\AdminController::class, 'updateUserStats'])->name('admin.users.stats.update');
    Route::post('/admin/users/{id}/tokens', [\App\Http\Controllers\AdminController::class, 'grantUserTokens'])->name('admin.users.tokens.grant');
    Route::post('/admin/jobs', [\App\Http\Controllers\AdminController::class, 'createJob'])->name('admin.jobs.create');
    Route::get('/admin/store/items', [\App\Http\Controllers\AdminController::class, 'storeItems'])->name('admin.store.items');
    Route::post('/admin/store/items', [\App\Http\Controllers\AdminController::class, 'createStoreItem'])->name('admin.store.items.create');
    Route::post('/admin/store/items/{id}/restock', [\App\Http\Controllers\AdminController::class, 'restockStoreItem'])->name('admin.store.items.restock');
    Route::get('/admin/store/balance', [\App\Http\Controllers\AdminController::class, 'storeBalance'])->name('admin.store.balance');
    Route::post('/admin/store/balance/transfer', [\App\Http\Controllers\AdminController::class, 'transferStoreBalanceToReserve'])->name('admin.store.balance.transfer');
    Route::post('/admin/store/balance/from-reserve', [\App\Http\Controllers\AdminController::class, 'transferReserveToStore'])->name('admin.store.balance.from_reserve');

    // Jobs system
    Route::get('/jobs', [JobsController::class, 'page'])->name('jobs.page');
    Route::get('/api/jobs', [JobsController::class, 'list'])->name('jobs.list');
    Route::post('/api/jobs/{key}/start', [JobsController::class, 'start'])->name('jobs.start');
    Route::post('/api/jobs/{key}/claim', [JobsController::class, 'claim'])->name('jobs.claim');

    // Store
    Route::get('/store', [StoreController::class, 'page'])->name('store.page');
    Route::get('/api/store/items', [StoreController::class, 'items'])->name('store.items');

    // Premium
    Route::get('/premium', [\App\Http\Controllers\PremiumController::class, 'page'])->name('premium.page');
    Route::get('/api/premium/status', [\App\Http\Controllers\PremiumController::class, 'status'])->name('premium.status');
    Route::post('/api/premium/buy', [\App\Http\Controllers\PremiumController::class, 'buy'])->name('premium.buy');
    Route::post('/api/premium/preview', [\App\Http\Controllers\PremiumController::class, 'preview'])->name('premium.preview');
    Route::post('/api/premium/heal', [\App\Http\Controllers\PremiumController::class, 'heal'])->name('premium.heal');
    Route::get('/api/store/balances', [StoreController::class, 'balances'])->name('store.balances');
    Route::post('/api/store/buy/{key}', [StoreController::class, 'buy'])->name('store.buy');

    // Travel
    Route::get('/travel', [\App\Http\Controllers\TravelController::class, 'page'])->name('travel.page');
    Route::post('/api/travel/step', [\App\Http\Controllers\TravelController::class, 'step'])->name('travel.step');

    // Inventory Page
    Route::get('/inventory', [InventoryController::class, 'page'])->name('inventory.page');

    // Inventory APIs
    Route::get('/api/inventory', [InventoryController::class, 'list'])->name('inventory.list');
    Route::post('/api/inventory/consume', [InventoryController::class, 'consume'])->name('inventory.consume');
    Route::post('/api/inventory/sell', [InventoryController::class, 'sell'])->name('inventory.sell');
    Route::post('/api/inventory/move-to-storage', [InventoryController::class, 'moveToStorage'])->name('inventory.move_to_storage');
    Route::post('/api/inventory/move-all-to-storage', [InventoryController::class, 'moveAllToStorage'])->name('inventory.move_all_to_storage');
    Route::post('/api/inventory/move-to-inventory', [InventoryController::class, 'moveToInventory'])->name('inventory.move_to_inventory');

    // Expeditions
    Route::get('/expeditions', [ExpeditionController::class, 'page'])->name('expeditions.page');
    Route::get('/api/expeditions', [ExpeditionController::class, 'catalog'])->name('expeditions.catalog');
    Route::get('/api/expeditions/my', [ExpeditionController::class, 'my'])->name('expeditions.my');
    Route::get('/api/expeditions/my-counts', [ExpeditionController::class, 'myCounts'])->name('expeditions.my_counts');
    Route::post('/api/expeditions/buy/{id}', [ExpeditionController::class, 'buy'])->name('expeditions.buy');
    Route::post('/api/expeditions/buy-level', [ExpeditionController::class, 'buyLevel'])->name('expeditions.buylevel');
    Route::post('/api/expeditions/start/{id}', [ExpeditionController::class, 'start'])->name('expeditions.start');
    Route::post('/api/expeditions/start-all-by-level', [ExpeditionController::class, 'startAllByLevel'])->name('expeditions.start_all_by_level');
    Route::post('/api/expeditions/claim/{id}', [ExpeditionController::class, 'claim'])->name('expeditions.claim');
    Route::post('/api/expeditions/claim-all', [ExpeditionController::class, 'claimAll'])->name('expeditions.claim_all');

    // Token Shop
    Route::get('/token-shop', [TokenShopController::class, 'page'])->name('token_shop.page');
    Route::get('/api/token-shop/balances', [TokenShopController::class, 'balances'])->name('token_shop.balances');
    Route::post('/api/token-shop/buy-slot', [TokenShopController::class, 'buySlot'])->name('token_shop.buy_slot');
    Route::post('/api/token-shop/buy-xp', [TokenShopController::class, 'buyXp'])->name('token_shop.buy_xp');
    Route::post('/api/token-shop/open-chest', [TokenShopController::class, 'openChest'])->name('token_shop.open_chest');
    Route::get('/api/token-shop/boosts', [TokenShopController::class, 'boosts'])->name('token_shop.boosts');
    Route::get('/api/token-shop/slot-grants', [TokenShopController::class, 'slotGrants'])->name('token_shop.slot_grants');
    Route::get('/api/token-shop/slot-stats', [TokenShopController::class, 'slotStats'])->name('token_shop.slot_stats');
    Route::post('/api/token-shop/exchange', [TokenShopController::class, 'exchangeToBank'])->name('token_shop.exchange');
    Route::post('/api/token-shop/convert', [TokenShopController::class, 'convertTokens'])->name('token_shop.convert');

    // Time Keeper stats
    Route::get('/keeper', [TimeKeeperController::class, 'page'])->name('keeper.page');
    Route::get('/keeper/stats', [TimeKeeperController::class, 'stats'])->name('keeper.stats');
    Route::get('/keeper/snapshots', [TimeKeeperController::class, 'snapshots'])->name('keeper.snapshots');
    Route::post('/keeper/admin/deposit', [TimeKeeperController::class, 'adminDepositFromUserToReserve'])->name('keeper.admin.deposit');
    Route::post('/keeper/admin/withdraw', [TimeKeeperController::class, 'adminWithdrawFromReserveToUser'])->name('keeper.admin.withdraw');
    Route::post('/keeper/admin/distribute', [TimeKeeperController::class, 'adminDistributeReserveToAll'])->name('keeper.admin.distribute');
    Route::post('/admin/guilds/xp', [GuildController::class, 'adminGrantXp'])->name('admin.guilds.grant_xp');

    // Guilds
    Route::get('/guilds', [GuildController::class, 'page'])->name('guilds.page');
    Route::get('/api/guilds/me', [GuildController::class, 'me'])->name('guilds.me');
    Route::get('/api/guilds', [GuildController::class, 'list'])->name('guilds.list');
    Route::get('/api/guilds/leaderboard', [GuildController::class, 'leaderboard'])->name('guilds.leaderboard');
    Route::post('/api/guilds/create', [GuildController::class, 'create'])->name('guilds.create');
    Route::post('/api/guilds/join', [GuildController::class, 'join'])->name('guilds.join');
    Route::post('/api/guilds/leave', [GuildController::class, 'leave'])->name('guilds.leave');
    Route::post('/api/guilds/disband', [GuildController::class, 'disband'])->name('guilds.disband');
    Route::post('/api/guilds/visibility', [GuildController::class, 'updateVisibility'])->name('guilds.visibility');
    Route::post('/api/guilds/donate-tokens', [GuildController::class, 'donateTokens'])->name('guilds.donate_tokens');
    Route::post('/api/guilds/requests/{id}/approve', [GuildController::class, 'approveRequest'])->name('guilds.requests.approve');
    Route::post('/api/guilds/requests/{id}/deny', [GuildController::class, 'denyRequest'])->name('guilds.requests.deny');
    Route::post('/api/guilds/members/{id}/role', [GuildController::class, 'updateMemberRole'])->name('guilds.members.role');
    Route::post('/api/guilds/transfer-leadership', [GuildController::class, 'transferLeadership'])->name('guilds.transfer_leadership');
    Route::post('/api/guilds/{id}/lock', [GuildController::class, 'adminLock'])->name('guilds.admin.lock');
    Route::post('/api/guilds/{id}/unlock', [GuildController::class, 'adminUnlock'])->name('guilds.admin.unlock');

    // Trades
    Route::get('/trades', [TradeController::class, 'page'])->name('trades.page');
    Route::get('/trades/{id}', [TradeController::class, 'pageShow'])->name('trades.page.show');
    Route::get('/api/trades/my-items', [TradeController::class, 'myItems'])->name('trades.my_items');
    Route::get('/api/trades/my-tokens', [TradeController::class, 'myTokens'])->name('trades.my_tokens');
    Route::get('/api/trades', [TradeController::class, 'list'])->name('trades.list');
    Route::get('/api/trades/{id}', [TradeController::class, 'show'])->name('trades.show');
    Route::post('/api/trades/create', [TradeController::class, 'create'])->name('trades.create');
    Route::post('/api/trades/{id}/lines/add', [TradeController::class, 'addLine'])->name('trades.lines.add');
    Route::post('/api/trades/{id}/lines/remove', [TradeController::class, 'removeLine'])->name('trades.lines.remove');
    Route::post('/api/trades/{id}/accept', [TradeController::class, 'accept'])->name('trades.accept');
    Route::post('/api/trades/{id}/unaccept', [TradeController::class, 'unaccept'])->name('trades.unaccept');
    Route::post('/api/trades/{id}/cancel', [TradeController::class, 'cancel'])->name('trades.cancel');
    Route::post('/api/trades/{id}/finalize', [TradeController::class, 'finalize'])->name('trades.finalize');

    // Stats and Leaderboards
    Route::post('/api/stats/steps', [\App\Http\Controllers\StatsController::class, 'addSteps'])->name('stats.add_steps');
    Route::get('/api/leaderboards/{period}', [\App\Http\Controllers\StatsController::class, 'leaderboard'])->name('leaderboards.period');
});

require __DIR__.'/auth.php';

