<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS in production so asset() URLs are always https://
        // This fixes mixed-content issues on Render and other HTTPS hosts
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
