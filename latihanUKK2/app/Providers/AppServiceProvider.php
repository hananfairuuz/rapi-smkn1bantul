<?php

namespace App\Providers;

use App\Services\MobileReleaseService;
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
        // Bagikan informasi rilis aplikasi mobile secara otomatis ke semua tampilan Blade
        View::composer('*', function ($view) {
            $view->with([
                'mobileAppVersion' => MobileReleaseService::version(),
                'mobileAppSize'    => MobileReleaseService::fileSize(),
            ]);
        });
    }
}
