<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-05
 * Time: 23:40
 */
namespace SQJ\Modules\Digiccy\Support;

use App\Events\UserCreditWithdrew;
use App\Models\User;
use App\Models\UserCredit;
use App\Models\UserWithdrawalLog;
use SQJ\Modules\Digiccy\Models\TransactionLog;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use App\Utils\Withdrawal as WithdrawalUtil;

class Withdrawal
{
    /**
     * 执行提币申请
     *
     * @param User $user
     * @param $creditType
     * @param $contract
     * @param $address
     * @param $money
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\ServerException
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public static function exec(User $user, $creditType, $contract, $address, $money)
    {
        // 验证提币参数
        self::checkWithdrawal($user, $creditType, $money);

        // 修改钱包余额
        UserCredit::setCredit($user, $creditType, -1 * $money, [
            ___('申请提币到钱包地址【%address%】', [
                '%address%'  => $address
            ]),
            ___('【%creditType%】不足，无法提币', [
                '%creditType%' => UserCredit::creditName($creditType)
            ])
        ]);

        // 计算提币手续费
        $fee = self::calculateWithdrawFee($money);

        // 添加提币记录
        UserWithdrawalLog::insert($user, $creditType, $money, $fee,
            SQJ_WITHDRAW_BY_CURRENCY, $address, $contract['symbol'], [
                [
                    'label' => '提币方式',
                    'type' => 'text',
                    'value' => strtoupper($contract['symbol'])
                ],
                [
                    'label' => '到账' . strtoupper($contract['symbol']),
                    'type' => 'text',
                    'value' => bcmul(($money - $fee), $contract['withdrawals'][$creditType], config('app.user_credit_place'))
                ]
            ]);

        // 添加事件
        event(new UserCreditWithdrew($user, $creditType, $money, $fee));
    }

    /**
     * 进行提币检测
     *
     * @param User $user 带提币的用户
     * @param $creditType
     * @param float $money 提币金额
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\ServerException
     * @throws \App\Exceptions\UserException
     */
    private static function checkWithdrawal(User $user, $creditType, $money)
    {
        // 获取配置参数
        $config = Settings::get(Settings::WITHDRAWAL_PARAMS);

        // 判断是否启用
        if (empty($config) || !$config['isEnabled'])
        {
            throw_user(___('系统未开启提币功能'));
        }

        // 判断是否指定提币方式
        if (empty($config['contractList']))
        {
            throw_user(___('系统未指定提币方式'));
        }

        // 判断周期
        if (!empty($config['period']))
        {
            // 获取本日星期
            if (!in_array(date('N'), $config['period']))
            {
                throw_user(___('今日系统不允许提币'));
            }
        }

        // 判断时间
        if (!empty($config['timeRange']))
        {
            // 当前时间
            $datetime = now_datetime();

            if ($datetime < $config['timeRange'][0] || $datetime > $config['timeRange'][1])
            {
                throw_user(___('当前时间系统不允许提币。可提币时间范围是：%startTime% ~ %endTime%。', [
                    '%startTime%' => $config['timeRange'][0],
                    '%endTime%' => $config['timeRange'][1]
                ]));
            }
        }

        // 判断提币次数
        if (!empty($config['dailyLimit']))
        {
            // 今日提币次数
            $todayCount = UserWithdrawalLog::todayCount($user);

            if ($todayCount >= $config['dailyLimit'])
            {
                throw_user(___('提币次数已达到上限。每人每天仅可进行 %dailyLimit% 笔提币', [
                    '%dailyLimit%' => $config['dailyLimit']
                ]));
            }
        }

        // 提币倍数
        if (isset($config['baseNum']) && $config['baseNum'] > 0)
        {
            if ($money % $config['baseNum'] != 0)
            {
                throw_user(___('提币金额必须是 %baseNum% 的整数倍', [
                    '%baseNum%' => $config['baseNum']
                ]));
            }
        }

        // 最低金额
        if (isset($config['minNum']) && $config['minNum'] > 0)
        {
            if ($money < $config['minNum'])
            {
                throw_user(___('提币金额不得低于 %minNum%。', [
                    '%minNum%' => $config['minNum']
                ]));
            }
        }

        // 最高金额
        if (isset($config['maxNum']) && $config['maxNum'] > 0)
        {
            if ($money > $config['maxNum'])
            {
                throw_user(___('提币金额不得高于 %maxNum%。', [
                    '%maxNum%' => $config['maxNum']
                ]));
            }
        }

        // 判断手续费
        if (isset($config['feeRate']) && $config['feeRate'] >= 100)
        {
            throw_user(___('提币手续费比例不得高于 100%。'));
        }
    }

    /**
     * 计算提币手续费
     *
     * @param float $money 提币金额
     * @return float
     */
    private static function calculateWithdrawFee($money)
    {
        // 获取配置参数
        $config = Settings::get(Settings::WITHDRAWAL_PARAMS);

        if (!empty($config) && !empty($config['feeRate']))
        {
            return $money * $config['feeRate'] / 100.0;
        }
        else
        {
            return 0;
        }
    }

    /**
     * 根据记录自动转账
     *
     * @param UserWithdrawalLog $withdrawal
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public static function transfer(UserWithdrawalLog $withdrawal)
    {
        // 获取钱包参数
        $params = Settings::get(Settings::WALLET_PARAMS);

        if (empty($params))
        {
            throw_user(___('尚未配置钱包参数，无法转账！'));
        }

        // 实际到账金额
        $realMoney = $withdrawal['otherInfo'][1]['value'];

        // 进行转账
        $translationHash = Ethereum::transfer($params['address'], $params['privateKey'], $withdrawal['account'],
            $realMoney, $withdrawal['realName']);

        // 添加交易记录
        TransactionLog::insert(UserWithdrawalLog::class, $withdrawal['id'],
            $translationHash, $withdrawal['realName']);
    }
}
