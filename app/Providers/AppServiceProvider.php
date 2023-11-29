<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
            if(!str_contains($query->sql,'select * from `jobs`'))
                Log::channel('database')->info(
                    sprintf('%6.2fms -- %s', $query->time, $query->sql),
                    $query->bindings
                );
        });
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
