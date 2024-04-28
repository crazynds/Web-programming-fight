<?php

namespace App\Providers;

use App\Models\File;
use App\Models\Problem;
use App\Models\Scorer;
use App\Models\SubmitRun;
use App\Models\TestCase;
use App\Observers\FileObserver;
use App\Observers\ProblemObserver;
use App\Observers\ScorerObserver;
use App\Observers\SubmitRunObserver;
use App\Observers\TestCaseObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        File::observe(FileObserver::class);
        TestCase::observe(TestCaseObserver::class);
        Scorer::observe(ScorerObserver::class);
        Problem::observe(ProblemObserver::class);
        SubmitRun::observe(SubmitRunObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
