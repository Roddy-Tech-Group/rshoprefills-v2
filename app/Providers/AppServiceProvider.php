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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, CreateWalletForNewUser::class);
        View::composer('*', CartComposer::class);
    }
}
