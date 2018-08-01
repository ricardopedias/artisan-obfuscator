<?php

namespace Obfuscator;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
		$this->commands([
            \Obfuscator\Commands\ObfuscateAppCommand::class
        ]);
    }
}
