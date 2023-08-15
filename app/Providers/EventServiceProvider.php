<?php

namespace App\Providers;

use App\Models\Notification;
use App\Observers\NotificationObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    // /**
    //  * The model observers for your application.
    //  *
    //  * @var array
    //  */
    // protected $observers = [
    //     Notification::class => [NotificationObserver::class],
    // ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Notification::observe(NotificationObserver::class);
    }
}
