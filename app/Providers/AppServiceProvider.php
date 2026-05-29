<?php

namespace App\Providers;

use App\Domain\Audit\Listeners\AuditLogListener;
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
use App\Listeners\TransactionPinNotificationListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Blade;
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

        // Transaction PIN lifecycle emails (created, changed, locked, reset).
        Event::subscribe(TransactionPinNotificationListener::class);

        // Append security-relevant events to the audit log.
        Event::subscribe(AuditLogListener::class);

        View::composer('*', CartComposer::class);

        // @money($amount, $code)      => "₦25,000.00"   (symbol + amount)
        // @moneyCode($amount, $code)  => "NGN 25,000.00" (ISO code + amount)
        // Delegates to App\Domain\Shared\Services\Money so we have one place
        // to evolve currency rendering as new codes / locales come online.
        Blade::directive('money', fn ($expr) => "<?php echo \App\Domain\Shared\Services\Money::format($expr); ?>");
        Blade::directive('moneyCode', fn ($expr) => "<?php echo \App\Domain\Shared\Services\Money::codeAmount($expr); ?>");
    }
}
