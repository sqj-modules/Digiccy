<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/11
 * Time: 4:46 下午
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use App\Exceptions\CommitException;
use SQJ\Modules\Digiccy\Models\TransactionLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueryTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:query-transaction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '查询数字货币的转账状态';

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
        $this->info('【' . now_datetime() . "】：*************************开始【查询转账交易状态】开始*******************");

        // 计数器
        $counter = 0;

        // 获取所有未完成的转账记录
        TransactionLog::query()
            ->whereNull('finished_at')
            ->where('status', 0)
            ->chunkById(50, function ($logs) use (&$counter) {
                foreach ($logs as $log)
                {
                    DB::beginTransaction();

                    try
                    {
                        // 查询状态
                        $status = $log->queryReceipt();

                        DB::commit();

                        // 输出记录信息
                        if ($status == 1)
                        {
                            $this->info('【' . now_datetime() . "】：【{$log['hash']}】交易成功。");
                        }
                        elseif ($status == -1)
                        {
                            $this->error('【' . now_datetime() . "】：{$log['hash']}】交易失败。");
                        }
                        else
                        {
                            $this->info('【' . now_datetime() . "】：{$log['hash']}】交易中…………");
                        }

                        // 累计计数器
                        ++$counter;
                    }
                    catch (\Exception $exception)
                    {
                        // 输出错误信息
                        $this->error(exception_with_position($exception));

                        // 回滚数据
                        DB::rollBack();
                    }
                }
            });

        $this->info('【' . now_datetime() . "】：*************************共计{$counter}转账交易查询结束*******************");

        $this->info('【' . now_datetime() . "】：*************************结束【查询转账交易状态】结束*******************");
    }
}
