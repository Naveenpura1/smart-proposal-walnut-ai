<?php

namespace App\Providers;

<<<<<<< HEAD
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
=======
use App\Models\Proposal;
use App\Policies\ProposalPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
>>>>>>> 9ad783d (Initial commit)

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin-only', function ($user) {
<<<<<<< HEAD
        return $user->role === 'admin';
        });
=======
            return $user->role === 'admin';
        });

        Gate::policy(Proposal::class, ProposalPolicy::class);
>>>>>>> 9ad783d (Initial commit)
    }
}
