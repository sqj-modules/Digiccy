<?php

namespace SQJ\Modules\Digiccy\Providers;

use App\Events\UserRegistered;
use SQJ\Modules\Digiccy\Listeners\InitUserWallet;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 初始化用户钱包
        Event::listen(UserRegistered::class, InitUserWallet::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
