<?php

namespace ISMS\ISMS;

use Illuminate\Support\ServiceProvider;

class ISMSServiceProvider extends ServiceProvider{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind('isms', function () {
            return new ISMS();
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['isms'];
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../config/isms.php' => config_path('isms.php'),
        ]);
    }
}
