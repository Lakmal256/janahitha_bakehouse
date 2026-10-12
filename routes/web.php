<?php

use App\Http\Controllers\SetupController;
use App\Livewire\Admin\Users as UserManagement;
use App\Livewire\Admin\Items as ItemManagement;
use App\Livewire\Admin\Routes as RouteManagement;
use App\Livewire\Admin\Shops as ShopManagement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! User::query()->exists()) {
        return redirect()->route('setup');
    }

    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/setup', [SetupController::class, 'create'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->middleware('throttle:6,1')->name('setup.store');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/admin/users', UserManagement::class)
        ->middleware('can:users')
        ->name('admin.users');

    Route::get('/admin/routes', RouteManagement::class)
        ->middleware('can:routes')
        ->name('admin.routes');

    Route::get('/admin/items', ItemManagement::class)
        ->middleware('can:items')
        ->name('admin.items');

    Route::get('/admin/shops', ShopManagement::class)
        ->middleware('can:shops')
        ->name('admin.shops');
});

