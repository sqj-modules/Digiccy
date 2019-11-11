<?php

namespace SQJ\Modules\Digiccy\Database\Seeds;

use SQJ\Modules\Digiccy\Models\Contract;
use SQJ\Modules\Digiccy\Models\Digiccy;
use Illuminate\Database\Seeder;

class DigiccyContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \App\Exceptions\DeveloperException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public function run()
    {
        // 充币参数
        $rechargeCredits = Digiccy::rechargeCredits();

        $rechargeList = [];

        foreach ($rechargeCredits as $credit)
        {
            $rechargeList[$credit] = 1;
        }

        // 提币参数
        $withdrawalCredits = Digiccy::withdrawalCredits();

        $withdrawalList = [];

        foreach ($withdrawalCredits as $credit)
        {
            $withdrawalList[$credit] = 1;
        }

        // eth
        Contract::updateOrCreate([
            'symbol' => 'eth'
        ], [
            'token' => 'Ethereum',
            'address' => ' ',
            'abi' => ' ',
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // USDT
        Contract::updateOrCreate([
            'symbol' => 'usdt'
        ], [
            'token' => 'Tether USD (USDT)',
            'address' => '0xdac17f958d2ee523a2206206994597c13d831ec7',
            'abi' => $this->abi('usdt'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // bnb
        Contract::updateOrCreate([
            'symbol' => 'bnb'
        ], [
            'token' => 'BNB (BNB)',
            'address' => '0xb8c77482e45f1f44de1745f52c74426c631bdd52',
            'abi' => $this->abi('bnb'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // leo
        Contract::updateOrCreate([
            'symbol' => 'leo'
        ], [
            'token' => 'Bitfinex LEO Token (LEO)',
            'address' => '0x2AF5D2aD76741191D15Dfe7bF6aC92d4Bd912Ca3',
            'abi' => $this->abi('leo'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // omg
        Contract::updateOrCreate([
            'symbol' => 'omg'
        ], [
            'token' => 'OmiseGO (OMG)',
            'address' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
            'abi' => $this->abi('omg'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // bat
        Contract::updateOrCreate([
            'symbol' => 'bat'
        ], [
            'token' => 'BAT (BAT)',
            'address' => '0x0D8775F648430679A709E98d2b0Cb6250d2887EF',
            'abi' => $this->abi('bat'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // omg
        Contract::updateOrCreate([
            'symbol' => 'usdc'
        ], [
            'token' => 'USD Coin (USDC)',
            'address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
            'abi' => $this->abi('usdc'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);

        // med
        Contract::updateOrCreate([
            'symbol' => 'med'
        ], [
            'token' => 'Med Chain (MED)',
            'address' => '0xfCDb773445b453af35683387F49F168F403e9936',
            'abi' => $this->abi('med'),
            'is_system' => 1,
            'recharges' => $rechargeList,
            'withdrawals' => $withdrawalList
        ]);
    }

    /**
     * @param $name
     * @return false|string
     * @throws \App\Exceptions\DeveloperException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    private function abi($name)
    {
        // 合约文件
        $filename = module_path('digiccy', "abi/{$name}.abi");

        // 判断合约文件是否存在
        if (!file_exists($filename))
        {
            throw_developer(___('代币【%contact%】的智能合约不存在！！！', [
                '%contact%' => $name
            ]));
        }

        return file_get_contents($filename);
    }
}
