<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-22
 * Time: 22:08
 */
namespace SQJ\Modules\Digiccy\Console;

use Illuminate\Console\Scheduling\Schedule;

class Kernel
{
    public function schedule(Schedule $schedule)
    {
        // 查询转账结果
        $schedule->command('digiccy:query-transaction')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(command_log_file('digiccy_query_transaction'));

        // 自动抓取行情
        $schedule->command('digiccy:poll-merged-ticker')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(command_log_file('digiccy_auto_poll_merged_ticker'));

        // 收集充值记录
        $schedule->command('digiccy:auto-recharge')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(command_log_file('digiccy_auto_recharge'));

        // 汇总入账
        $schedule->command('digiccy:gather-user-wallet')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(command_log_file('digiccy_gather_user_wallet'));
    }
}
