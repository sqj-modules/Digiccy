<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-12
 * Time: 15:23
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use App\Exceptions\CommitException;
use SQJ\Modules\Digiccy\Models\UserWallet;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use SQJ\Modules\Digiccy\Support\Settings;
use Illuminate\Console\Command;
use phpseclib\Math\BigInteger;

class GatherUserWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:gather-user-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从用户钱包中收集余额';

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
     * @throws \App\Exceptions\DeveloperException
     */
    public function handle()
    {
        $this->info('【' . now_datetime() . "】：*************************开始【自动汇入用户钱包余额】开始*******************");

        // 计数器
        $counter = 0;

        // 获取钱包参数
        $walletParams = Settings::get(Settings::WALLET_PARAMS);

        // 判断参数
        if (empty($walletParams) || !isset($walletParams['address']) || empty($walletParams['address']))
        {
            throw_developer(___('尚未配置钱包信息，无法操作！！！'));
        }

        // 合约符号
        $symbol = config('digiccy.symbol');

        // 遍历所有的钱包
        UserWallet::query()
            ->chunkById(100, function ($wallets) use (&$counter, $walletParams, $symbol) {
                foreach ($wallets as $wallet)
                {
                    try
                    {
                        if ($symbol)
                        {
                            // 查询余额
                            $contractBalance = Ethereum::balanceOf($symbol, $wallet['address']);

                            if ($contractBalance->compare(new BigInteger(0)) > 0)
                            {
                                // 查询余额
                                $ethBalance = Ethereum::balance($wallet['address'], true);

                                // 检测交易费用
                                $canTransfer = Ethereum::checkTransactionFee($walletParams, $wallet['address'], $ethBalance, $symbol);

                                // 进行转账
                                if ($canTransfer)
                                {
                                    Ethereum::transfer($wallet['address'], $wallet['privateKey'], $walletParams['address'], $contractBalance, $symbol);
                                }
                            }
                        }
                        else
                        {
                            // 查询余额
                            $ethBalance = Ethereum::balance($wallet['address'], true);

                            // 检测交易费用
                            $canTransfer = Ethereum::checkTransactionFee($walletParams, $wallet['address'], $ethBalance);

                            // 进行转账
                            if ($canTransfer)
                            {
                                Ethereum::transfer($wallet['address'], $wallet['privateKey'], $walletParams['address'], $ethBalance);
                            }
                        }

                        $this->info("钱包【{$wallet['address']}】自动汇入成功。");

                        // 累计计数器
                        ++$counter;
                    }
                    catch (\Exception $exception)
                    {
                        $this->error("钱包【{$wallet['address']}】自动汇入失败。");
                    }
                }
            });

        $this->info('【' . now_datetime() . "】：*************************共计{$counter}钱包自动汇入成功*******************");

        $this->info('【' . now_datetime() . "】：*************************结束【自动汇入用户钱包余额】结束*******************");
    }
}
