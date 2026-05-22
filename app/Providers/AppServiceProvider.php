<?php

namespace App\Providers;

use App\Domain\Notification\Listeners\AdminEventNotifier;
use App\Domain\Notification\Listeners\CreateDefaultPreferencesListener;
use App\Domain\Notification\Listeners\SendFulfillmentNotificationListener;
use App\Domain\Notification\Listeners\SendOrderConfirmationListener;
use App\Domain\Notification\Listeners\SendWalletCreditNotificationListener;
use App\Domain\Notification\Listeners\SendWalletDebitNotificationListener;
use App\Domain\Notification\Listeners\SendWelcomeEmailListener;
use App\Domain\Notification\Providers\MailProviderInterface;
use App\Domain\Notification\Providers\ResendProvider;
use App\Domain\Wallet\Events\WalletCredited;
use App\Domain\Wallet\Events\WalletDebited;
use App\Http\View\Composers\CartComposer;
use App\Listeners\CommerceNotificationListener;
use App\Listeners\CreateWalletForNewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            MailProviderInterface::class,
            ResendProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, CreateWalletForNewUser::class);
        Event::listen(Registered::class, CreateDefaultPreferencesListener::class);
        Event::listen(Registered::class, SendWelcomeEmailListener::class);

        Event::listen(WalletCredited::class, SendWalletCreditNotificationListener::class);
        Event::listen(WalletDebited::class, SendWalletDebitNotificationListener::class);

        Event::subscribe(SendOrderConfirmationListener::class);
        Event::subscribe(SendFulfillmentNotificationListener::class);

        Event::subscribe(CommerceNotificationListener::class);

        // Fan key platform events out to the admin-dashboard notification feed.
        Event::subscribe(AdminEventNotifier::class);

        View::composer('*', CartComposer::class);
    }
}
