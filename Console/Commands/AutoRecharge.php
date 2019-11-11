<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-06
 * Time: 17:24
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use App\Models\UserCredit;
use SQJ\Modules\Digiccy\Models\AutoRechargeLog;
use SQJ\Modules\Digiccy\Models\Contract;
use SQJ\Modules\Digiccy\Models\UserWallet;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use SQJ\Modules\Digiccy\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Web3\Utils;

class AutoRecharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:autoRecharge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从区块链中收集交易记录并自动充值';

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
     * @throws \App\Exceptions\DeveloperException
     * @throws \Throwable
     */
    public function handle()
    {
        // 获取钱包参数
        $walletParams = Settings::get(Settings::WALLET_PARAMS);

        // 判断参数
        if (empty($walletParams) || !isset($walletParams['address']) || empty($walletParams['address']))
        {
            throw_developer(___('尚未配置钱包信息，无法操作！！！'));
        }

        // 获取区块高度
        $blockNumber = Settings::getBlockNumber();

        // 循环次数
        $counter = 0;

        // 获取所有的钱包地址
        $addressDictionary  = UserWallet::addressDictionary();

        // 合约符号
        $symbol = config('digiccy.symbol');
        // 合约地址
        $contractAddress = Contract::address($symbol);

        //
        do
        {
            DB::beginTransaction();

            try
            {
                // 获取指定区块的所有交易
                $transactions = Ethereum::blockTransactions(Utils::toHex($blockNumber, true));

                // 如果不存在交易，则跳出循环
                if ($transactions === false)
                {
                    $this->error("Block #{$blockNumber} 暂不存在！");

                    break;
                }

                // 处理的地址数量
                $addressCounter = 0;

                // 遍历所有交易做处理
                foreach ($transactions as $transaction)
                {
                    // 转账地址
                    $fromAddress = $transaction->to;

                    // 其他代币
                    if ($contractAddress)
                    {
                        // 如果不存在指定地址，则跳过
                        if ($fromAddress != $contractAddress)
                        {
                            continue;
                        }

                        // 解析合约数据
                        $data = Ethereum::parseContractData($transaction->input);

                        // 充值地址
                        $fromAddress = $data['address'];

                        if (!$addressDictionary->has($fromAddress))
                        {
                            continue;
                        }

                        $amount = Utils::toHex($data['value'], true);
                    }
                    // ETH
                    else
                    {
                        // 如果不存在指定地址，则跳过
                        if (!$addressDictionary->has($fromAddress))
                        {
                            continue;
                        }

                        $amount = $transaction->value;
                    }

                    // 获取用户
                    $user = UserWallet::getUserByAddress($fromAddress);

                    // 转账金额
                    $amount = Ethereum::hexToCredit($amount, $symbol);

                    // 添加用户余额
                    UserCredit::setCredit($user, UserCredit::W_BALANCE, $amount, 'ETH钱包充值');

                    // 添加充值记录
                    AutoRechargeLog::insert($user, $amount, $transaction->gas, $transaction->gasPrice, $fromAddress, $transaction->hash);

                    // 输出信息
                    $this->info("用户【{$user['account']}】充值【{$amount}】成功");

                    ++$addressCounter;
                }

                // 输出记录
                $this->info('Block #' . $blockNumber . ' 的所有交易已遍历处理，共条 '
                    . count($transactions) . ' 记录，发现 ' . $addressCounter . ' 条系统地址。');

                // 累加计数
                ++$counter;

                // 累加区块高度
                ++$blockNumber;

                // 更新区块高度
                Settings::setBlockNumber($blockNumber);

                DB::commit();
            }
            catch (\Exception $exception)
            {
                DB::rollBack();
            }

        }
        while($counter <= 30);
    }
}
