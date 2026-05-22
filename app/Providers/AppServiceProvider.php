<?php

namespace App\Providers;

use App\Http\View\Composers\CartComposer;
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
            \App\Domain\Notification\Providers\MailProviderInterface::class,
            \App\Domain\Notification\Providers\ResendProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, CreateWalletForNewUser::class);
        Event::listen(Registered::class, \App\Domain\Notification\Listeners\CreateDefaultPreferencesListener::class);
        Event::listen(Registered::class, \App\Domain\Notification\Listeners\SendWelcomeEmailListener::class);

        Event::listen(\App\Domain\Wallet\Events\WalletCredited::class, \App\Domain\Notification\Listeners\SendWalletCreditNotificationListener::class);
        Event::listen(\App\Domain\Wallet\Events\WalletDebited::class, \App\Domain\Notification\Listeners\SendWalletDebitNotificationListener::class);

        Event::subscribe(\App\Domain\Notification\Listeners\SendOrderConfirmationListener::class);
        Event::subscribe(\App\Domain\Notification\Listeners\SendFulfillmentNotificationListener::class);

        Event::subscribe(\App\Listeners\CommerceNotificationListener::class);
        Event::subscribe(\App\Listeners\TransactionPinNotificationListener::class);
        View::composer('*', CartComposer::class);
    }
}
