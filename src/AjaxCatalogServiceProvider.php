<?php

namespace Vaden\AjaxCatalog;

use Illuminate\Support\ServiceProvider;
use Event;

class AjaxCatalogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //config files
        $this->publishes([
            __DIR__ . '/../config/ajaxCatalog.php' => $this->app->configPath() . '/' . 'ajaxCatalog.php',
        ], 'config');

        //migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        //js
        $this->publishes([__DIR__ . '/../public/js/' => base_path('resources/assets/js')]);

        //views
        $this->loadViewsFrom(__DIR__.'/../views/elements/', 'ajaxCatalogElements');

        $this->publishes([
            __DIR__.'/../views/ajaxCatalogPage.blade.php' => resource_path('views/ajaxCatalog/ajaxCatalogPage.blade.php'),
        ]);
        $this->publishes([
            __DIR__.'/../views/pages/' => resource_path('views/ajaxCatalog/pages/'),
        ]);
		$this->publishes([
            __DIR__.'/../views/elements/' => resource_path('views/ajaxCatalog/elements/'),
        ]);

        //translation
        $this->publishes([
            __DIR__.'/../lang/' => resource_path('lang/'),
        ]);

        //resources
        $this->publishes([__DIR__ . '/../resources/' => resource_path("assets/vendor/ajaxcatalog")], 'resources');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ajaxCatalog.php', 'ajaxCatalog');
    }
}
