<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-24
 * Time: 10:39
 */
namespace SQJ\Modules\Digiccy\Http\Clients\Admin\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\UserCredit;
use SQJ\Modules\Digiccy\Models\Contract;
use SQJ\Modules\Digiccy\Models\Currency;
use SQJ\Modules\Digiccy\Models\Digiccy;
use SQJ\Modules\Digiccy\Support\Settings;
use SQJ\Modules\Digiccy\Support\Withdrawal;
use App\Utils\QrCode;
use Illuminate\Support\Arr;

class SettingsModule extends ApiModule
{
    /**
     * @var string 模块名称
     */
    protected $name = '参数设置';

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 获取所有
            '1000' => [
                'method' => 'currencyList',
                'permission' => 'digiccy.settings.currency.list',
                'label' => '货币列表'
            ],
            // 切换货币
            '1001' => [
                'method' => 'changeCurrency',
                'permission' => 'digiccy.settings.currency.edit',
                'label' => '编辑货币'
            ],
            // 获取钱包参数
            '2000' => [
                'method' => 'getWalletParams',
                'permission' => 'digiccy.settings.wallet',
                'label' => '钱包参数'
            ],
            // 设置钱包参数
            '2001' => [
                'method' => 'setWalletParams',
                'permission' => 'digiccy.settings.wallet'
            ],
            // 获取提现设置
            '3000' => [
                'method' => 'getWithdrawal',
                'permission' => 'digiccy.settings.withdrawal'
            ],
            // 设置提现
            '3001' => [
                'method' => 'setWithdrawal',
                'permission' => 'digiccy.settings.withdrawal',
                'label' => '提币参数'
            ],
            // 获取启用的钱包
            '9000' => 'enabledCredits'
        ];
    }

    /**
     * 模块权限
     *
     * @return array|bool
     */
    public function permissions()
    {
        return [
            'items' => [
                '1000', '1001', '2000', '3001'
            ]
        ];
    }

    /**
     * 货币列表
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function currencyList()
    {
        return $this->pageList(Currency::class);
    }

    /**
     * 修改货币
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function changeCurrency()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'isDisabled' => 'required|boolean'
        ]);

        // 获取货币
        $currency = Currency::getById($data['id']);

        // 修改数据
        $currency->change($data);

        return ___('修改货币成功');
    }

    /**
     * 获取钱包的参数设置
     *
     * @return array
     */
    protected function getWalletParams()
    {
        // 获取参数
        $params = Settings::get(Settings::WALLET_PARAMS);

        return $params ?: [];
    }

    /**
     * 设置钱包参数
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     */
    protected function setWalletParams()
    {
        // 验证数据
        $data = $this->validate([
            'address' => 'required|string',
            'privateKey' => 'required|string'
        ]);

        // 钱包二维码
        $data['qrCode'] = QrCode::generate($data['address'], 300);

        // 设置参数
        Settings::set(Settings::WALLET_PARAMS, $data);

        return ___('设置钱包参数成功');
    }

    /**
     * 获取提现设置
     *
     * @return array
     */
    protected function getWithdrawal()
    {
        // 提币参数
        $params = Settings::get(Settings::WITHDRAWAL_PARAMS);

        // 允许提币的类型
        $withdrawalCredits = Digiccy::withdrawalCredits();

        // 允许提币的声明
        $balanceNames = [];

        foreach ($withdrawalCredits as $type)
        {
            if (UserCredit::checkEnabled($type))
            {
                $balanceNames[] = UserCredit::creditName($type);
            }
        }

        return [
            'balanceName' => implode('、', $balanceNames),
            'contractList' => Contract::enabledDictionary(),
            'params' => $params ?: []
        ];
    }

    /**
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function setWithdrawal($callback)
    {
        // 是否启用
        $isEnabled = $this->requiredParam('isEnabled');

        // 使用的合约
        $contractList = $this->optionalParam('contractList', []);

        // 验证是否为数组
        if (!is_array($contractList))
        {
            throw_developer('字段【contractList】必须是数组');
        }

        // 如果开启了提现，至少选择一个合约
        if ($isEnabled && count($contractList) <= 0)
        {
            throw_user('开启提币至少指定一种提币方式');
        }

        // 提现周期
        $period = $this->optionalParam('period', []);
        // 提现周期进行排序
        $period = array_values(Arr::sort($period));

        // 时间范围
        $timeRange = $this->optionalParam('timeRange', []);

        // 验证是否为数组
        if (!is_array($timeRange))
        {
            throw_developer('字段【timeRange】必须是数组');
        }

        // 验证长度
        if (!empty($timeRange) && count($timeRange) != 2)
        {
            throw_developer('字段【timeRange】必须有开始时间和结束时间');
        }

        // 每日限制
        $dailyLimit = $this->optionalParam('dailyLimit', 0);

        // 提现基数
        $baseNum = $this->optionalParam('baseNum', 0);

        // 最低金额
        $minNum = $this->optionalParam('minNum', 0);

        // 最高金额
        $maxNum = $this->optionalParam('maxNum', 0);

        // 手续费比例
        $feeRate = $this->optionalParam('feeRate', 0);

        // 到账周期
        $arrivePeriod = $this->optionalParam('arrivePeriod', 0);

        // 提现声明
        $statement = $this->optionalParam('statement', '');

        Settings::set(Settings::WITHDRAWAL_PARAMS, [
            'isEnabled' => $isEnabled,
            'contractList' => $contractList,
            'period' => $period,
            'timeRange' => $timeRange,
            'dailyLimit' => intval($dailyLimit),
            'baseNum' => intval($baseNum),
            'minNum' => floatval($minNum),
            'maxNum' => floatval($maxNum),
            'feeRate' => floatval($feeRate),
            'arrivePeriod' => intval($arrivePeriod),
            'statement' => $statement
        ]);

        // 清除相关缓存
        Contract::flushCache();

        // 添加记录
        $callback('修改提币设置');

        return ___('设置提币参数成功');
    }

    /**
     * 启用的钱包
     *
     * @return array
     */
    protected function enabledCredits()
    {
        return Digiccy::enabledCredits();
    }
}
