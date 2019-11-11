<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 23:09
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use App\Models\User;
use SQJ\Modules\Digiccy\Models\Currency;
use SQJ\Modules\Digiccy\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RepairUserWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:repair-user-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修复未创建用户钱包的问题';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('【' . now_datetime() . "】：*************************开始【修复用户的系统钱包】开始*******************");

        // 计数器
        $counter = 0;

        DB::table('users as u')
            ->leftJoin('digiccy_user_wallets as uw', 'u.id', '=', 'uw.user_id')
            ->select(['u.id', 'uw.address'])
            ->whereNull('uw.address')
            ->whereNull('u.deleted_at')
            ->whereNull('uw.deleted_at')
            ->orderByDesc('u.id')
            ->chunk(100, function ($list) use (&$counter) {

                foreach ($list as $item)
                {
                    $user = User::find($item->id);

                    if ($user)
                    {
                        UserWallet::init($user);

                        $this->info("初始化完成用户【{$user['account']}】的钱包。");

                        ++$counter;
                    }
                }
            });

        $this->info('【' . now_datetime() . "】：*************************共计{$counter}用户的钱包初始化成功*******************");

        $this->info('【' . now_datetime() . "】：*************************结束【修复用户的系统钱包】结束*******************");
    }
}
