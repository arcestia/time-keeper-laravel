<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\TimeKeeperController;
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

    // Time Keeper stats
    Route::get('/keeper', [TimeKeeperController::class, 'page'])->name('keeper.page');
    Route::get('/keeper/stats', [TimeKeeperController::class, 'stats'])->name('keeper.stats');
    Route::post('/keeper/admin/deposit', [TimeKeeperController::class, 'adminDepositFromUserToReserve'])->name('keeper.admin.deposit');
    Route::post('/keeper/admin/withdraw', [TimeKeeperController::class, 'adminWithdrawFromReserveToUser'])->name('keeper.admin.withdraw');
    Route::post('/keeper/admin/distribute', [TimeKeeperController::class, 'adminDistributeReserveToAll'])->name('keeper.admin.distribute');
});

require __DIR__.'/auth.php';

