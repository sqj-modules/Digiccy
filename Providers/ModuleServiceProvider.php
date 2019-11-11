<?php

namespace SQJ\Modules\Digiccy\Providers;

use SQJ\Modules\Digiccy\Support\Contracts\Market\Manager;
use Caffeinated\Modules\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module services.
     *
     * @return void
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public function boot()
    {
        $this->loadTranslationsFrom(module_path('digiccy', 'Resources/Lang', 'app'), 'digiccy');
        $this->loadViewsFrom(module_path('digiccy', 'Resources/Views', 'app'), 'digiccy');
        $this->loadMigrationsFrom(module_path('digiccy', 'Database/Migrations', 'app'), 'digiccy');
        if (!Config::has('digiccy'))
        {
            $this->loadConfigsFrom(module_path('digiccy', 'Config', 'app'));
        }
        $this->loadFactoriesFrom(module_path('digiccy', 'Database/Factories', 'app'));

        $this->publishes([
            module_path('digiccy', 'Config/digiccy.php') => config_path('digiccy.php'),
        ]);

        // 加载常量
        require module_path('digiccy', 'Support/defines.php');
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register()
    {
        // 注册路由服务器
        $this->app->register(RouteServiceProvider::class);

        // 注册事件服务器
        $this->app->register(EventServiceProvider::class);

        $this->app->singleton('DigiccyMarket', function ($app) {
            return new Manager($app);
        });
    }
}
