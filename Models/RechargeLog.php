<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-23
 * Time: 15:07
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\UserCredit;
use SQJ\Modules\Digiccy\Support\Facades\DigiccyMarket;
use Illuminate\Support\Arr;

class RechargeLog extends Digiccy
{
    /**
     * 添加充币记录
     *
     * @param User $user
     * @param Contract $contract
     * @param $creditType
     * @param $address
     * @param $amount
     * @return bool
     * @throws \App\Exceptions\UserException
     */
    public static function insert(User $user, Contract $contract, $creditType, $address, $amount)
    {
        // 是否存在申请中的充值
        $exists = self::query()
            ->where('user_id', $user['id'])
            ->where('contract_id', $contract['id'])
            ->where('status', 0)
            ->exists();

        if ($exists)
        {
            throw_user(___('存在未处理的充币申请，请稍后重试！'));
        }

        // 创建记录
        $log = new RechargeLog();

        // 用户ID
        $log['userId'] = $user['id'];
        // 钱包类型
        $log['contractId'] = $contract['id'];
        // 钱包地址
        $log['address'] = $address;
        // 充值到账数量
        $log['amount'] = $amount;
        // 到账钱包
        $log['creditType'] = $creditType;
        // 到账金额
        if (isset($contract['recharges']) && isset($contract['recharges'][$creditType]))
        {
            $log['credit'] = bcmul($amount, $contract['recharges'][$creditType], config('app.user_credit_place'));
        }
        else
        {
            $log['credit'] = $amount;
        }
        // 状态
        $log['status'] = 0;

        return $log->save();
    }

    /**
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联合约
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * 关联管理员表
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }

    /**
     * 分页获取数据
     *
     * @param $lastId
     * @param $page
     * @param array $condition
     * @return array
     */
    public static function pageList($lastId, $page, $condition = [])
    {
        // 创建查询构造器
        $builder = self::query()
            ->with(['user' => function ($query) {
                $query->select(['id', User::accountType() . ' as account', 'nickname', 'avatar']);
            }, 'contract' => function ($query) {
                $query->select(['id', 'symbol']);
            }, 'adminUser' => function($query) {
                $query->select(['id', 'username', 'nickname']);
            }]);

        // 用户ID
        if (isset($condition['userId']) && $condition['userId'] !== '')
        {
            $builder->where('user_id', $condition['userId']);
        }

        // 用户信息
        if (isset($condition['user']) && $condition['user'] !== '')
        {
            $builder->whereHas('user', function ($query) use ($condition) {
                $query->where('id', intval($condition['user']))
                    ->orWhere(User::accountType(), 'like', "%{$condition['user']}%")
                    ->orWhere('nickname', 'like', "%{$condition['user']}%");
            });
        }

        // 货币信息
        if (isset($condition['contract']) && $condition['contract'] !== '')
        {
            $builder->whereHas('contract', function ($query) use ($condition) {
                $query->Where('symbol', 'like', "%{$condition['contract']}%");
            });
        }

        // 钱包类型
        if (isset($condition['creditType']) && $condition['creditType'] !== '')
        {
            $builder->where('credit_type', $condition['creditType']);
        }

        // 状态
        if (isset($condition['status']) && $condition['status'] !== '')
        {
            $builder->where('status', intval($condition['status']));
        }

        // 申请时间
        if (isset($condition['timeRange']) && !empty($condition['timeRange']))
        {
            $builder->whereBetween(self::CREATED_AT, $condition['timeRange']);
        }

        $data = self::paginate($builder, $lastId, $page);

        if (self::fromAdmin())
        {
            foreach ($data['list'] as &$item)
            {
                $item['creditName'] = UserCredit::creditName($item['creditType']);
            }
        }
        else
        {
            foreach ($data['list'] as &$item)
            {
                $temp = Arr::only($item, [
                    'id', 'address', 'amount', 'credit', 'status', 'acceptedAt',
                    'rejectedAt', 'rejectedReason', 'createdAt'
                ]);

                // 钱包名称
                $temp['creditName'] = UserCredit::creditName($item['creditType']);
                // 合约名称
                $temp['contractSymbol'] = strtoupper($item['contract']['symbol']);

                $item = $temp;
            }
        }

        return $data;
    }

    /**
     * 接受申请
     *
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public function accept()
    {
        // 判断状态
        if ($this['status'] != 0)
        {
            throw_user(___('该申请记录已被处理'));
        }

        // 修改状态
        $this['status'] = 1;
        // 接受时间
        $this['acceptedAt'] = now_datetime();
        // 操作员
        $this['adminId'] = self::adminId();

        if ($this->save())
        {
            // 充值到余额
            UserCredit::setCredit($this['user'], $this['creditType'], $this['credit'],
                ___('充值【%amount%】【%contract%】', [
                    '%amount%' => $this['amount'],
                    '%contract%' => strtoupper($this['contract']['symbol'])
                ]));
        }
    }

    /**
     * 驳回申请
     *
     * @param $reason
     * @return bool
     * @throws \App\Exceptions\UserException
     */
    public function reject($reason)
    {
        // 判断状态
        if ($this['status'] != 0)
        {
            throw_user(___('该申请记录已被处理'));
        }

        // 修改状态
        $this['status'] = -1;
        // 驳回时间
        $this['rejectedAt'] = now_datetime();
        // 驳回原因
        $this['rejectedReason'] = $reason;
        // 操作员
        $this['adminId'] = self::adminId();

        return $this->save();
    }
}
