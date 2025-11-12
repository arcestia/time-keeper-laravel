<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\TimeKeeperController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/bank', [BankController::class, 'page'])->name('bank.page');
    Route::get('/bank/data', [BankController::class, 'index'])->name('bank.index');
    Route::get('/bank/user-time', [BankController::class, 'userTime'])->name('bank.user_time');
    Route::post('/bank/passcode', [BankController::class, 'setPasscode'])->name('bank.passcode');
    Route::post('/bank/login', [BankController::class, 'login'])->name('bank.login');
    Route::post('/bank/lock', [BankController::class, 'lock'])->name('bank.lock');
    Route::post('/bank/deposit', [BankController::class, 'deposit'])->name('bank.deposit');
    Route::post('/bank/withdraw', [BankController::class, 'withdraw'])->name('bank.withdraw');
    Route::post('/bank/transfer', [BankController::class, 'transfer'])->name('bank.transfer');

    // Player stats
    Route::get('/api/me/stats', [StatsController::class, 'me'])->name('stats.me');
    Route::patch('/api/me/stats', [StatsController::class, 'updateMe'])->name('stats.updateMe');

    // Admin
    Route::get('/admin', [\App\Http\Controllers\AdminController::class, 'page'])->name('admin.page');
    Route::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'usersSearch'])->name('admin.users.search');
    Route::get('/admin/users/{id}/stats', [\App\Http\Controllers\AdminController::class, 'getUserStats'])->name('admin.users.stats');
    Route::patch('/admin/users/{id}/stats', [\App\Http\Controllers\AdminController::class, 'updateUserStats'])->name('admin.users.stats.update');
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

    // Time Keeper stats
    Route::get('/keeper', [TimeKeeperController::class, 'page'])->name('keeper.page');
    Route::get('/keeper/stats', [TimeKeeperController::class, 'stats'])->name('keeper.stats');
    Route::get('/keeper/snapshots', [TimeKeeperController::class, 'snapshots'])->name('keeper.snapshots');
    Route::post('/keeper/admin/deposit', [TimeKeeperController::class, 'adminDepositFromUserToReserve'])->name('keeper.admin.deposit');
    Route::post('/keeper/admin/withdraw', [TimeKeeperController::class, 'adminWithdrawFromReserveToUser'])->name('keeper.admin.withdraw');
    Route::post('/keeper/admin/distribute', [TimeKeeperController::class, 'adminDistributeReserveToAll'])->name('keeper.admin.distribute');
});

require __DIR__.'/auth.php';

