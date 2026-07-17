<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPropertyController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

/* User Blade interfaces */
Route::redirect('/', '/login');
Route::view('/login', 'user.auth.login')->name('user.login');
Route::view('/register', 'user.auth.register')->name('user.register');

Route::get('/forgot-password', [PasswordResetController::class, 'showUserForgot'])
    ->name('user.password.forgot');
Route::post('/forgot-password', [PasswordResetController::class, 'sendUserCodeWeb'])
    ->middleware('throttle:5,1')
    ->name('user.password.send-code');
Route::get('/reset-password', [PasswordResetController::class, 'showUserReset'])
    ->name('user.password.reset.form');
Route::post('/reset-password', [PasswordResetController::class, 'resetUserPasswordWeb'])
    ->middleware('throttle:10,1')
    ->name('user.password.reset');

Route::prefix('app')->name('user.')->group(function (): void {
    Route::view('/', 'user.dashboard')->name('dashboard');
    Route::view('/account', 'user.account')->name('account');

    Route::view('/properties', 'user.properties.index')->name('properties.index');
    Route::view('/properties/create', 'user.properties.create')->name('properties.create');
    Route::view('/my-properties', 'user.properties.my')->name('properties.my');
    Route::get('/properties/{id}/edit', fn (int $id) => view('user.properties.edit', compact('id')))->name('properties.edit');
    Route::get('/properties/{id}', fn (int $id) => view('user.properties.show', compact('id')))->name('properties.show');

    Route::view('/search', 'user.search')->name('search');
    Route::view('/nearby', 'user.nearby')->name('nearby');
    Route::view('/favorites', 'user.favorites')->name('favorites');
    Route::view('/notifications', 'user.notifications')->name('notifications');

    Route::view('/chats', 'user.chats.index')->name('chats.index');
    Route::get('/chats/{propertyId}/{otherUserId}', function (int $propertyId, int $otherUserId) {
        return view('user.chats.show', compact('propertyId', 'otherUserId'));
    })->name('chats.show');

    Route::redirect('/chats/{id}', '/app/chats')->name('chats.legacy');
});

/* Admin Blade only */
Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
Route::get('/admin/forgot-password', [PasswordResetController::class, 'showAdminForgot'])->name('admin.password.forgot');
Route::post('/admin/forgot-password', [PasswordResetController::class, 'sendAdminCode'])
    ->middleware('throttle:5,1')
    ->name('admin.password.send-code');
Route::get('/admin/reset-password', [PasswordResetController::class, 'showAdminReset'])->name('admin.password.reset.form');
Route::post('/admin/reset-password', [PasswordResetController::class, 'resetAdminPassword'])
    ->middleware('throttle:10,1')
    ->name('admin.password.reset');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', [AdminPropertyController::class, 'index'])->name('dashboard');
    Route::get('/properties/{property}', [AdminPropertyController::class, 'show'])->name('properties.show');
    Route::patch('/properties/{property}/approve', [AdminPropertyController::class, 'approve'])->name('properties.approve');
    Route::patch('/properties/{property}/reject', [AdminPropertyController::class, 'reject'])->name('properties.reject');
    Route::delete('/properties/{property}', [AdminPropertyController::class, 'destroy'])->name('properties.destroy');
    Route::get('/documents/{document}/download', [AdminPropertyController::class, 'downloadDocument'])->name('documents.download');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
