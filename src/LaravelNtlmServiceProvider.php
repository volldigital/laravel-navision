<?php

namespace VOLLdigital\LaravelNtlm;

use Illuminate\Support\ServiceProvider;

class LaravelNtlmServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/ntlm.php' => config_path('ntlm.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client(config('ntlm'));
        });
    }

}
