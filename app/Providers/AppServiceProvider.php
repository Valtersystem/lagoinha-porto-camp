<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Vite::prefetch(concurrency: 3);

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isAdministrator() ? true : null;
        });

        Gate::define('manage-users', function (User $user): bool {
            return $user->hasPermission('users.manage');
        });

        Gate::define('manage-camps', function (User $user): bool {
            return $user->hasPermission('camps.manage');
        });

        Gate::define('manage-payments', function (User $user): bool {
            return $user->hasPermission('payments.manage');
        });

        Gate::define('manage-operations', function (User $user): bool {
            return $user->hasPermission('operations.manage')
                || $user->hasPermission('camps.manage')
                || $user->hasPermission('payments.manage');
        });
    }
}
