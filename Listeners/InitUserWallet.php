<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-06
 * Time: 15:32
 */
namespace SQJ\Modules\Digiccy\Listeners;

use App\Events\UserRegistered;
use SQJ\Modules\Digiccy\Models\UserWallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class InitUserWallet implements ShouldQueue
{
    /**
     * 处理队列
     *
     * @param UserRegistered $event
     * @throws \App\Exceptions\DeveloperException
     */
    public function handle(UserRegistered $event)
    {
        UserWallet::init($event->user());
    }

    /**
     * 失败处理
     *
     * @param UserRegistered $event
     * @param \Exception $exception
     */
    public function failed(UserRegistered $event, \Exception $exception)
    {
        Log::error('【初始化用户钱包失败】' . $exception->getMessage());
    }
}
