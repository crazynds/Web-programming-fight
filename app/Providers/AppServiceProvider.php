<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ContestService;
use App\Services\VJudgeService;
use Illuminate\Auth\Access\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        DB::listen(function ($query) {
            if (config('app.env') === 'production' && $query->time < 50) {
                return;
            }
            if (! str_contains($query->sql, '`jobs`' && ! str_contains($query->sql, '`pulse_values`'))) {
                Log::channel('database')->info(
                    sprintf('%6.2fms -- %s', $query->time, $query->sql),
                    $query->bindings
                );
            }
        });
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Gate::define('import-zip', function (User $user) {
            return $user->isAdmin()
                ? Response::allow()
                : Response::deny('You must be an administrator.');
        });
        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });
        Paginator::useBootstrap();
        View::share('vjudgeService', new VJudgeService);
        $this->app->singleton(ContestService::class, function () {
            return new ContestService;
        });
    }
}
