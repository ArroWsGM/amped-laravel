<?php

namespace Arrowsgm\Amped;

use AMP_Autoloader;
use Arrowsgm\Amped\Exceptions\AmpPluginNotFoundException;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Arrowsgm\Amped\Facades\Amped;

class AmpedLaravelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/amped.php', 'amped'
        );

        $loader = AliasLoader::getInstance();
        $loader->alias('Amped', Amped::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     * @throws AmpPluginNotFoundException|\Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        $this->resolveAmpLoader();

        $this->publishes([
            __DIR__.'/../config/amped.php' => config_path('amped.php'),
        ], 'amped-config');

        $this->registerFacades();
        $this->registerConfig();
        $this->registerMiddleware();
    }

    /**
     * Resolve amp-wp plugin path for regular app and testing environment
     *
     * @throws AmpPluginNotFoundException
     */
    private function resolveAmpLoader()
    {
        $amp_path = base_path('vendor/ampproject/amp-wp');
        $amp_autoloader_path = '/includes/class-amp-autoloader.php';

        if (file_exists($amp_path . $amp_autoloader_path)) {
            //normal laravel application
            $this->ampAutoload($amp_path . $amp_autoloader_path, $amp_path);
        } elseif (file_exists(dirname(__FILE__) . '/../vendor/ampproject/amp-wp' . $amp_autoloader_path)) {
            //testing environment setBasePath not working for service provider
            //maybe getPackageProviders fires before setUp
            $amp_path = dirname(__FILE__) . '/../vendor/ampproject/amp-wp';
            $this->ampAutoload($amp_path . $amp_autoloader_path, $amp_path);
        } else {
            throw new AmpPluginNotFoundException('Amp plugin autoloader class not found');
        }
    }

    /**
     * Run amp-wp autoload and register required constants
     *
     * @param $path
     * @param $amp_path
     */
    private function ampAutoload($path, $amp_path) {
        require_once $path;

        if (!defined('AMP__DIR__')) {
            define('AMP__DIR__', $amp_path);
        }

        AMP_Autoloader::register();
    }

    /**
     * Register any bindings to the app.
     *
     * @return void
     */
    protected function registerFacades()
    {
        $this->app->singleton('Amped', function ($app) {
            return new \Arrowsgm\Amped\Amped();
        });
    }

    /**
     * Register any default fields to the app.
     *
     * @return void
     */
    private function registerConfig()
    {
        Amped::embeds(config('amped.embeds'));
        Amped::sanitizers(config('amped.sanitizers'));
        Amped::args(config('amped.args'));
    }

    /**
     * Register any default fields to the app.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function registerMiddleware()
    {
        $router = $this->app->make(Router::class);

        foreach(config('amped.middleware', []) as $name => $class ){
            if($name || class_exists($class)) {
                $router->aliasMiddleware($name, $class);
            }
        }
    }
}
