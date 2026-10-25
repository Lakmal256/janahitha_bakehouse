<?php

use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\DayController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeLeaveController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\SalaryAdvanceController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\SetupController;
use App\Livewire\Admin\Items as ItemManagement;
use App\Livewire\Admin\Routes as RouteManagement;
use App\Livewire\Admin\Shops as ShopManagement;
use App\Livewire\Admin\Users as UserManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::view('/', 'bakehouse')->name('home');

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

    Route::view('/admin/employees', 'bakehouse')
        ->middleware('can:employees')
        ->name('admin.employees');
});

Route::prefix('api')->name('api.')->group(function (): void {
    Route::get('session', [SessionController::class, 'show'])->name('session.show');
    Route::post('session', [SessionController::class, 'store'])->name('session.store');
    Route::delete('session', [SessionController::class, 'destroy'])->name('session.destroy');

    Route::middleware(['auth', 'active'])->group(function (): void {
        Route::get('routes', [RouteController::class, 'index'])->name('routes.index');
        Route::match(['put', 'patch'], 'routes/{key}', [RouteController::class, 'update'])->name('routes.update');
        Route::delete('routes/{key}', [RouteController::class, 'destroy'])->name('routes.destroy');

        Route::get('shops', [ShopController::class, 'index'])->name('shops.index');
        Route::match(['put', 'patch'], 'shops/{key}', [ShopController::class, 'update'])->name('shops.update');
        Route::delete('shops/{key}', [ShopController::class, 'destroy'])->name('shops.destroy');

        Route::get('config', [CatalogController::class, 'index'])->name('config.index');
        Route::put('config/{key}', [CatalogController::class, 'update'])->name('config.update');
        Route::delete('items/{key}', [CatalogController::class, 'destroy'])->name('items.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::match(['put', 'patch'], 'users/{key}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{key}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('days', [DayController::class, 'index'])->name('days.index');
        Route::match(['put', 'patch'], 'days/{key}', [DayController::class, 'update'])->name('days.update');
        Route::delete('days/{key}', [DayController::class, 'destroy'])->name('days.destroy');

        Route::get('bills', [BillController::class, 'index'])->name('bills.index');
        Route::match(['put', 'patch'], 'bills/{key}', [BillController::class, 'update'])->name('bills.update');
        Route::delete('bills/{key}', [BillController::class, 'destroy'])->name('bills.destroy');

        Route::middleware('can:employees')->group(function (): void {
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::match(['put', 'patch'], 'employees/{key}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::delete('employees/{key}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

            Route::get('salary-advances', [SalaryAdvanceController::class, 'index'])->name('salary-advances.index');
            Route::match(['put', 'patch'], 'salary-advances/{key}', [SalaryAdvanceController::class, 'update'])->name('salary-advances.update');
            Route::delete('salary-advances/{key}', [SalaryAdvanceController::class, 'destroy'])->name('salary-advances.destroy');

            Route::get('employee-leaves', [EmployeeLeaveController::class, 'index'])->name('employee-leaves.index');
            Route::match(['put', 'patch'], 'employee-leaves/{key}', [EmployeeLeaveController::class, 'update'])->name('employee-leaves.update');
            Route::delete('employee-leaves/{key}', [EmployeeLeaveController::class, 'destroy'])->name('employee-leaves.destroy');
        });

        Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    });
});

Route::post('/deploy/finalize', function (Request $request) {
    $secret = config('app.deploy_secret');
    if (empty($secret) || $request->header('X-Deploy-Token') !== $secret) {
        abort(403, 'Unauthorized');
    }

    $zipPath = base_path('release.zip');
    if (! file_exists($zipPath)) {
        return response()->json(['error' => 'release.zip not found at '.$zipPath], 404);
    }

    try {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo(base_path());
            $zip->close();
            @unlink($zipPath);
        } else {
            return response()->json(['error' => 'Failed to open release.zip archive'], 500);
        }

        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return response()->json([
            'status' => 'success',
            'message' => 'Release extracted and Laravel caches refreshed!',
        ]);
    } catch (Exception $e) {
        Log::error('Deployment failed: '.$e->getMessage());

        return response()->json(['error' => $e->getMessage()], 500);
    }
});
