<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 08:53
 */
namespace SQJ\Modules\Digiccy\Http\Clients\Portal\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\UserCredit;
use App\Models\UserWithdrawalLog;
use SQJ\Modules\Digiccy\Models\AutoRechargeLog;
use SQJ\Modules\Digiccy\Models\Contract;
use SQJ\Modules\Digiccy\Models\Digiccy;
use SQJ\Modules\Digiccy\Models\RechargeLog;
use SQJ\Modules\Digiccy\Models\UserWallet;
use SQJ\Modules\Digiccy\Support\Settings;
use SQJ\Modules\Digiccy\Support\Withdrawal;
use App\Rules\UserSafeword;
use Illuminate\Validation\Rule;

/**
 * @ApiSector (财务相关)
 *
 * Class WalletModule
 *
 * @package SQJ\Modules\Digiccy\Http\Clients\Portal\v1
 */
class FinanceModule extends ApiModule
{

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 充币参数
            '1000' => 'rechargeParams',
            // 申请充币
            '1001' => 'recharge',
            // 充币记录
            '1002' => 'rechargeLogs',
            // 自动充值记录
            '1003' => 'autoRechargeLogs',
            // 提币参数
            '2000' => 'withdrawalParams',
            // 申请提币
            '2001' => 'withdraw',
            // 提币记录
            '2002' => 'withdrawalLogs'
        ];
    }

    /**
     * @ApiTitle (充币参数)
     *
     * @ApiReturnParams (name="isAuto", type="boolean", required=true, description="是否自动充币。true：自动充币；false：线下申请充币")
     * @ApiReturnParams (name="qrCode", type="string", required=true, description="充币钱包二维码")
     * @ApiReturnParams (name="address", type="string", required=true, description="充币钱包地址")
     * @ApiReturnParams (name="contractList", type="array[object]", required=false, description="【仅当线下充币时返回】充币合约列表。")
     * @ApiReturnParams (name="creditList", type="array[object]", required=false, description="【仅当线下充币时返回】可充币的钱包列表。")
     *
     * @ApiReturnParams (name="label", group="contractList", type="string", required=true, description="合约符号")
     * @ApiReturnParams (name="value", group="contractList", type="number", required=true, description="合约ID")
     * @ApiReturnParams (name="recharges", group="contractList", type="Object", required=true, description="合约充值时各钱包的比例")
     *
     * @ApiReturnParams (name="钱包类型", group="contractList.recharges", type="number", required=true, description="该参数返回对象，key为钱包类型，value为充值比例")
     *
     * @ApiReturnParams (name="label", group="creditList", type="string", required=true, description="钱包名称")
     * @ApiReturnParams (name="value", group="creditList", type="string", required=true, description="钱包类型")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function rechargeParams()
    {
        // 根据是否开启自动充值返回不同的信息
        if (config('digiccy.auto_recharge'))
        {
            // 当前用户
            $user = $this->user();

            // 获取用户钱包
            $wallet = UserWallet::getByUser($user);

            return [
                'isAuto' => true,
                'qrCode' => $wallet['qrCode'],
                'address' => $wallet['address']
            ];
        }
        else
        {
            // 获取设置参数
            $params = Settings::get(Settings::WALLET_PARAMS);

            // 可充币的类型
            $rechargeCredits = Digiccy::rechargeCredits();

            // 提币的钱包列表
            $creditList = [];

            foreach ($rechargeCredits as $credit)
            {
                $creditList[] = [
                    'label' => UserCredit::creditName($credit),
                    'value' => $credit
                ];
            }

            return [
                'isAuto' => false,
                'qrCode' => isset($params['qrCode']) ? $params['qrCode'] : '',
                'address' => isset($params['address']) ? $params['address'] : '',
                'contractList' => Contract::enabledDictionary(),
                'creditList' => $creditList
            ];
        }
    }

    /**
     * @ApiTitle (申请充币)
     *
     * @ApiSummary (该接口仅限平台启用线下充币时使用)
     *
     * @ApiParams (name="address", type="string", required=true, description="用户充币使用的钱包地址")
     * @ApiParams (name="contract", type="number", required=true, description="代币合约ID")
     * @ApiParams (name="creditType", type="string", required=true, description="钱包类型")
     * @ApiParams (name="amount", type="number", required=true, description="充币金额")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function recharge()
    {
        // 验证数据
        $data = $this->validate([
            'address' => 'required|string',
            'contract' => 'required|numeric',
            'creditType' => [
                'required', 'string', Rule::in(Digiccy::rechargeCredits())
            ],
            'amount' => 'required|numeric|min:0'
        ]);

        // 当前用户
        $user = $this->user();

        // 获取合约
        $contract = Contract::getById($data['contract']);

        if ($contract['isDisabled'])
        {
            throw_user(___('智能合约【%contract%】已禁用', [
                '%contract%' => strtoupper($contract['symbol'])
            ]));
        }

        // 添加充值记录
        RechargeLog::insert($user, $contract, $data['creditType'], $data['address'] , abs($data['amount']));

        return ___('充币申请成功');
    }

    /**
     * @ApiTitle (线下充币记录)
     *
     * @ApiSummary (仅当使用线下充币时才可访问此接口)
     *
     * @ApiParams (name="lastId", type="number", required=true, description="最新记录ID。首次传0，之后传接口返回的lastId")
     * @ApiParams (name="page", type="number", required=true, description="请求数据页码")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="记录ID")
     * @ApiReturnParams (name="status", group="list", type="number", required=true, description="充值状态。0：申请中；1：充币成功 ；-1：充币失败")
     * @ApiReturnParams (name="address", group="list", type="string", required=true, description="充币地址")
     * @ApiReturnParams (name="amount", group="list", type="number", required=true, description="充币金额")
     * @ApiReturnParams (name="contractSymbol", group="list", type="string", required=true, description="合约符号")
     * @ApiReturnParams (name="creditName", group="list", type="string", required=true, description="到账钱包名称")
     * @ApiReturnParams (name="credit", group="list", type="number", required=true, description="钱包到账金额")
     * @ApiReturnParams (name="createdAt", group="list", type="string", required=true, description="充币申请时间")
     * @ApiReturnParams (name="acceptedAt", group="list", type="string", required=true, description="充币审核通过时间")
     * @ApiReturnParams (name="rejectedAt", group="list", type="string", required=true, description="充币审核拒绝时间")
     * @ApiReturnParams (name="rejectedReason", group="list", type="string", required=true, description="充币审核拒绝原因")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function rechargeLogs()
    {
        // 当前用户
        $user = $this->user();

        $condition['userId'] = $user['id'];

        return $this->pageList(RechargeLog::class, $condition);
    }

    /**
     * @ApiTitle (自动充币记录)
     *
     * @ApiSummary (仅自动充币时使用该接口)
     *
     * @ApiParams (name="lastId", type="number", required=true, description="最新记录ID。首次传0，之后传接口返回的lastId")
     * @ApiParams (name="page", type="number", required=true, description="请求数据页码")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="记录ID")
     * @ApiReturnParams (name="has", group="list", type="string", required=true, description="交易HASH")
     * @ApiReturnParams (name="address", group="list", type="string", required=true, description="交易地址")
     * @ApiReturnParams (name="amount", group="list", type="number", required=true, description="充币金额")
     * @ApiReturnParams (name="gas", group="list", type="number", required=true, description="gas")
     * @ApiReturnParams (name="gasPrice", group="list", type="string", required=true, description="gasPrice")
     * @ApiReturnParams (name="fee", group="list", type="number", required=true, description="充币花费的旷工费")
     * @ApiReturnParams (name="createdAt", group="list", type="string", required=true, description="充币时间")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function autoRechargeLogs()
    {
        // 当前用户
        $user = $this->user();

        return $this->pageList(AutoRechargeLog::class, [
            'userId' => $user['id']
        ]);
    }

    /**
     * @ApiTitle (提币参数)
     *
     * @ApiReturnParams (name="params", type="object", required=true, description="提币参数")
     * @ApiReturnParams (name="creditList", type="array[object]", required=true, description="可提币的钱包列表")
     * @ApiReturnParams (name="contractList", type="array[object]", required=true, description="可提币的合约列表")
     *
     * @ApiReturnParams (name="isEnabled", group="params", type="boolean", required=true, description="是否开启提币。true：开启；false：关闭")
     * @ApiReturnParams (name="period", group="params", type="array[number]", required=true, description="可提币周期。从1到7分别对应从星期一到星期日，若为空则不做约束")
     * @ApiReturnParams (name="timeRange", group="params", type="array[string]", required=true, description="可提币的时间范围。第一项是开始时间，第二项为结束时间")
     * @ApiReturnParams (name="dailyLimit", group="params", type="number", required=true, description="每日最大提币次数。若为0则不做约束")
     * @ApiReturnParams (name="maxNum", group="params", type="number", required=true, description="可提币最大数额。若为0则不约束")
     * @ApiReturnParams (name="minNum", group="params", type="number", required=true, description="可提币最小数额。若为0则不约束")
     * @ApiReturnParams (name="baseNum", group="params", type="number", required=true, description="可提币基数，提币金额必须是此数值的整数倍")
     * @ApiReturnParams (name="feeRate", group="params", type="number", required=true, description="提币手续费。")
     * @ApiReturnParams (name="arrivePeriod", group="params", type="number", required=true, description="到账周期。T+")
     * @ApiReturnParams (name="statement", group="params", type="string", required=true, description="提现声明")
     *
     * @ApiReturnParams (name="name", group="creditList", type="string", required=true, description="钱包类型")
     * @ApiReturnParams (name="label", group="creditList", type="string", required=true, description="钱包名称")
     * @ApiReturnParams (name="value", group="creditList", type="number", required=true, description="钱包余额")
     *
     * @ApiReturnParams (name="label", group="contractList", type="string", required=true, description="合约符号")
     * @ApiReturnParams (name="value", group="contractList", type="number", required=true, description="合约ID")
     * @ApiReturnParams (name="withdrawals", group="contractList", type="object", required=true, description="不同钱包的提币比例")
     * @ApiReturnParams (name="creditType", group="contractList", type="number", required=true, description="key为钱包类型；value为提币比例")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\ServerException
     */
    protected function withdrawalParams()
    {
        // 获取提现参数
        $params = Settings::get(Settings::WITHDRAWAL_PARAMS);

        // 当前用户
        $user = $this->user();

        // 可提币的类型
        $withdrawalCredits = Digiccy::withdrawalCredits();

        // 提币的钱包列表
        $creditList = [];

        foreach ($withdrawalCredits as $credit)
        {
            if (UserCredit::checkEnabled($credit))
            {
                $creditList[] = [
                    'name' => $credit,
                    'label' => UserCredit::creditName($credit),
                    'value' => UserCredit::getCredit($user, $credit)
                ];
            }
        }

        return [
            'params' => $params ?: [],
            'creditList' => $creditList,
            'contractList' => Contract::withdrawingDictionary()
        ];
    }

    /**
     * @ApiTitle (申请提币)
     *
     * @ApiParams (name="creditType", type="string", required=true, description="钱包类型")
     * @ApiParams (name="contract", type="number", required=true, description="合约ID")
     * @ApiParams (name="address", type="string", required=true, description="提币到账钱包地址")
     * @ApiParams (name="amount", type="number", required=true, description="提币金额")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\ServerException
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    protected function withdraw()
    {
        // 验证数据
        $data = $this->validate([
            'creditType' => 'required|string',
            'contract' => 'required|numeric',
            'address' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'safeword' => [
                'required', 'string', new UserSafeword()
            ]
        ]);

        // 当前用户
        $user = $this->user();

        // 验证安全密码
        $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);

        // 获取合约
        $contract = Contract::getById($data['contract']);

        // 执行提币
        Withdrawal::exec($user, $data['creditType'], $contract, $data['address'], abs($data['amount']));

        return ___('申请提币成功');
    }

    /**
     * @ApiTitle (提币记录)
     *
     * @ApiParams (name="lastId", type="number", required=true, description="最新记录ID。首次传0，之后传接口返回的lastId")
     * @ApiParams (name="page", type="number", required=true, description="请求数据页码")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="记录ID")
     * @ApiReturnParams (name="creditName", group="list", type="string", required=true, description="提币钱包名称")
     * @ApiReturnParams (name="account", group="list", type="string", required=true, description="提币地址")
     * @ApiReturnParams (name="money", group="list", type="number", required=true, description="提币金额")
     * @ApiReturnParams (name="fee", group="list", type="number", required=true, description="提币手续费")
     * @ApiReturnParams (name="otherInfo", group="list", type="array[object]", required=true, description="用于原样输出的信息")
     * @ApiReturnParams (name="status", group="list", type="number", required=true, description="提币状态。0：申请中；1：提币成功；-1：申请被驳回")
     * @ApiReturnParams (name="createdAt", group="list", type="string", required=true, description="提币申请时间")
     * @ApiReturnParams (name="remittedAt", group="list", type="string", required=true, description="打款时间")
     * @ApiReturnParams (name="rejectedAt", group="list", type="string", required=true, description="拒绝时间")
     * @ApiReturnParams (name="reason", group="list", type="string", required=true, description="拒绝时间")
     *
     * @ApiReturnParams (name="type", group="list.otherInfo", type="string", required=true, description="信息类型。text：文本内容；image：图片内容")
     * @ApiReturnParams (name="label", group="list.otherInfo", type="string", required=true, description="显示标签")
     * @ApiReturnParams (name="value", group="list.otherInfo", type="string", required=true, description="信息值。当type为image值为图片链接")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function withdrawalLogs()
    {
        // 当前用户
        $user = $this->user();

        // 用户信息
        $condition['userId'] = $user['id'];
        // 到账类型
        $condition['accountType'] = SQJ_WITHDRAW_BY_CURRENCY;

        return $this->pageList(UserWithdrawalLog::class, $condition);
    }
}
