<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }

        Gate::policy(User::class, UserPolicy::class);

        foreach (User::PERMISSIONS as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->canPermission($permission));
        }
    }
}
