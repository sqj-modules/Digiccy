<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-25
 * Time: 22:38
 */
namespace SQJ\Modules\Digiccy\Http\Clients\Admin\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\UserWithdrawalLog;
use SQJ\Modules\Digiccy\Models\AutoRechargeLog;
use SQJ\Modules\Digiccy\Models\Contract;
use SQJ\Modules\Digiccy\Models\Currency;
use SQJ\Modules\Digiccy\Models\Digiccy;
use SQJ\Modules\Digiccy\Models\FailRechargeLog;
use SQJ\Modules\Digiccy\Models\RechargeLog;
use SQJ\Modules\Digiccy\Support\Withdrawal;

class WalletModule extends ApiModule
{
    /**
     * @var string 模块名称
     */
    protected $name = '钱包管理';

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 启用的合约
            '1000' => 'enabledContracts',
            // 充币记录
            '2000' => [
                'method' => 'rechargeLogs',
                'permission' => [
                    'digiccy.wallet.recharge.applyList' => '申请记录',
                    'digiccy.wallet.recharge.successList' => '成功记录',
                    'digiccy.wallet.recharge.failList' => '失败记录'
                ]
            ],
            // 通过充币申请
            '2001' => [
                'method' => 'acceptRecharge',
                'permission' => 'digiccy.wallet.recharge.accept',
                'label' => '通过申请'
            ],
            // 驳回充币申请
            '2002' => [
                'method' => 'rejectRecharge',
                'permission' => 'digiccy.wallet.recharge.reject',
                'label' => '驳回申请'
            ],
            // 充币的选项
            '2009' => 'rechargeParams',
            // 自动充币成功记录
            '2100' => [
                'method' => 'autoRechargeSuccess',
                'permission' => 'digiccy.wallet.recharge.auto.success',
                'label' => '成功记录'
            ],
            // 自动充币失败记录
            '2101' => [
                'method' => 'autoRechargeFail',
                'permission' => 'digiccy.wallet.recharge.auto.fail',
                'label' => '失败记录'
            ],
            // 提币记录
            '3000' => [
                'method' => 'withdrawalLogs',
                'permission' => [
                    'digiccy.wallet.withdrawal.applyList' => '申请记录',
                    'digiccy.wallet.withdrawal.successList' => '成功记录',
                    'digiccy.wallet.withdrawal.failList' => '失败记录'
                ]
            ],
            // 通过提币申请
            '3001' => [
                'method' => 'acceptWithdrawal',
                'permission' => 'digiccy.wallet.withdrawal.accept',
                'label' => '通过申请'
            ],
            // 驳回提币申请
            '3002' => [
                'method' => 'rejectWithdrawal',
                'permission' => 'digiccy.wallet.withdrawal.reject',
                'label' => '驳回申请'
            ]
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
            'groups' => [
                [
                    'permission' => 'digiccy.wallet.recharge',
                    'label' => '充币管理',
                    'interfaces' => [
                        '2000', '2001', '2002'
                    ]
                ],
                [
                    'permission' => 'digiccy.wallet.recharge.auto',
                    'label' => '自动充币',
                    'interfaces' => [
                        '2100', '2101'
                    ]
                ],
                [
                    'permission' => 'digiccy.wallet.withdrawal',
                    'label' => '提币管理',
                    'interfaces' => [
                        '3000', '3001', '3002'
                    ]
                ]
            ]
        ];
    }

    /**
     * 启用的合约
     *
     * @return array
     */
    protected function enabledContracts()
    {
        return [
            'recharges' => Contract::enabledDictionary(),
            'withdrawals' => Contract::withdrawingDictionary()
        ];
    }

    /**
     * 充币记录
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function rechargeLogs()
    {
        return $this->pageList(RechargeLog::class);
    }

    /**
     * 通过充币申请
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function acceptRecharge($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取申请日志
        $log = RechargeLog::getById($data['id']);

        // 通过申请
        $log->accept();

        $callback("通过充币申请【{$log['id']}】");

        return ___('充币申请通过成功');
    }

    /**
     * 驳回充币申请
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function rejectRecharge($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'reason' => 'required|string|max:200'
        ]);

        // 获取申请日志
        $log = RechargeLog::getById($data['id']);

        // 驳回申请
        $log->reject($data['reason']);

        $callback("驳回充币申请【{$log['id']}】");

        return ___('充币申请驳回成功');
    }

    /**
     * 充币的相关参数
     *
     * @return array
     */
    protected function rechargeParams()
    {
        $credits = Digiccy::enabledCredits();

        return [
            'creditList' => $credits['recharge'],
            'contractList' => Contract::enabledDictionary()
        ];
    }

    /**
     * 自动充值成功列表
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function autoRechargeSuccess()
    {
        return $this->pageList(AutoRechargeLog::class);
    }

    /**
     * 自动转账失败记录
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function autoRechargeFail()
    {
        return $this->pageList(FailRechargeLog::class);
    }

    /**
     * 提币记录
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function withdrawalLogs()
    {
        // 到账类型
        $condition['accountType'] = SQJ_WITHDRAW_BY_CURRENCY;

        return $this->pageList(UserWithdrawalLog::class, $condition);
    }

    /**
     * 通过提币申请
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function acceptWithdrawal($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取申请日志
        $log = UserWithdrawalLog::getById($data['id']);

        // 通过申请
        $log->accept();

        // 自动执行转账
        if (config('digiccy.auto_withdrawal'))
        {
            Withdrawal::transfer($log);
        }

        $callback("通过提币申请【{$log['id']}】");

        return ___('提币申请通过成功');
    }

    /**
     * 驳回提币申请
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function rejectWithdrawal($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'reason' => 'required|string|max:200'
        ]);

        // 获取申请日志
        $log = UserWithdrawalLog::getById($data['id']);

        // 驳回申请
        $log->reject($data['reason']);

        $callback("驳回提币申请【{$log['id']}】");

        return ___('提币申请驳回成功');
    }
}
