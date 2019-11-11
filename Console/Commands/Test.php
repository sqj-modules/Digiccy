<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-05
 * Time: 17:19
 */
namespace SQJ\Modules\Digiccy\Console\Commands;

use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use Illuminate\Console\Command;
use RuntimeException;
use Web3\Contract;
use Web3\Web3;
use xtype\Ethereum\Client as EthereumClient;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digiccy:test {method : 要测试的命令}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '代码测试专用，可测试常用命令';

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
        // 要测试的方法
        $method = $this->argument('method');

        // 判断方法是否存在
        if (method_exists($this, $method))
        {
            $this->$method();
        }
    }

    protected function createAccount()
    {
        $account = Ethereum::createAccount();

//        $result = $client->eth_accounts();
        $this->info(json_encode($account));
    }

    protected function accounts()
    {
        $web3 = new Web3(config('digiccy.ethereum.wallet_address'));

        $eth = $web3->eth;

        echo 'Eth Get Account and Balance' . PHP_EOL;
        $eth->accounts(function ($err, $accounts) use ($eth) {
            if ($err !== null) {
                echo 'Error: ' . $err->getMessage();
                return;
            }
            foreach ($accounts as $account) {
                echo 'Account: ' . $account . PHP_EOL;

                $eth->getBalance($account, function ($err, $balance) {
                    if ($err !== null) {
                        echo 'Error: ' . $err->getMessage();
                        return;
                    }
                    echo 'Balance: ' . $balance . PHP_EOL;
                });
            }
        });
    }

    protected function contract()
    {
        $web3 = new Web3(config('digiccy.ethereum.wallet_address'));
        $abi = file_get_contents(module_path('digiccy', 'contract.abi'));

        $contract = new Contract($web3->provider, $abi);
        $web3->eth->accounts(function ($err, $accounts) use ($contract) {
            if ($err === null) {
                if (isset($accounts)) {
                    $accounts = $accounts;
                } else {
                    throw new RuntimeException('Please ensure you have access to web3 json rpc provider.');
                }
                $fromAccount = $accounts[0];
                $toAccount = $accounts[1];
                $contract->at('0xfcdb773445b453af35683387f49f168f403e9936');

                $contract->getEth();
                //交易
                //用户地址
                $form = "0x4678F56043A6F8ECc891D5fBA93EFfa0e460f191";
                $key  = "022dc384c99f76d99d3f600253e52cf50ab029f501059dbec987dea3d8138e13";
                $opts = [
                    'from' => $fromAccount,
                    'data' => '0x',
                    'value' => 0,
                    'key' => $key
                ];
                $contract->send('transfer',$toAccount, 1, $opts, function ($err, $result) {

                });
            }
        });
    }

    protected function transfer()
    {
        $web3 = new Web3(config('digiccy.ethereum.wallet_address'));
        $eth = $web3->eth;
        $eth->accounts(function ($err, $accounts) use ($eth) {
            if ($err !== null) {
                echo 'Error: ' . $err->getMessage();
                return;
            }
            $fromAccount = $accounts[0];
            $toAccount = $accounts[1];

            // get balance
            $eth->getBalance($fromAccount, function ($err, $balance) use($fromAccount) {
                if ($err !== null) {
                    echo 'Error: ' . $err->getMessage();
                    return;
                }
                echo $fromAccount . ' Balance: ' . $balance . PHP_EOL;
            });
            $eth->getBalance($toAccount, function ($err, $balance) use($toAccount) {
                if ($err !== null) {
                    echo 'Error: ' . $err->getMessage();
                    return;
                }
                echo $toAccount . ' Balance: ' . $balance . PHP_EOL;
            });

            // send transaction
            $eth->sendTransaction([
                'from' => $fromAccount,
                'to' => $toAccount,
                'value' => '0x11'
            ], function ($err, $transaction) use ($eth, $fromAccount, $toAccount) {
                if ($err !== null) {
                    echo 'Error: ' . $err->getMessage();
                    return;
                }
                echo 'Tx hash: ' . $transaction . PHP_EOL;

                // get balance
                $eth->getBalance($fromAccount, function ($err, $balance) use($fromAccount) {
                    if ($err !== null) {
                        echo 'Error: ' . $err->getMessage();
                        return;
                    }
                    echo $fromAccount . ' Balance: ' . $balance . PHP_EOL;
                });
                $eth->getBalance($toAccount, function ($err, $balance) use($toAccount) {
                    if ($err !== null) {
                        echo 'Error: ' . $err->getMessage();
                        return;
                    }
                    echo $toAccount . ' Balance: ' . $balance . PHP_EOL;
                });
            });
        });
    }
}
