<?php

namespace App\Providers;

use App\Services\DataIsolationService;
use Illuminate\Support\ServiceProvider;

class DataIsolationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataIsolationService::class, function ($app) {
            return new DataIsolationService();
        });
    }

    public function boot(): void
    {
    }
}
