<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            if (config('app.env') === 'production' && $query->time < 20) {
                return;
            }
            Log::channel('database')->info(
                sprintf('%6.2fms -- %s', $query->time, $query->sql),
                $query->bindings
            );
        });
        if(config('app.env') === 'production') {
            \Url::forceScheme('https');
        }
    }
}
