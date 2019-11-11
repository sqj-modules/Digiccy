<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-10
 * Time: 10:24
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\User;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use Web3\Utils;

class AutoRechargeLog extends Digiccy
{
    /**
     * 添加自动转账记录
     *
     * @param User $user
     * @param $amount
     * @param $gas
     * @param $gasPrice
     * @param $address
     * @param $hash
     * @return bool
     */
    public static function insert(User $user, $amount, $gas, $gasPrice, $address, $hash)
    {
        $log = new AutoRechargeLog();

        // 用户信息
        $log['userId'] = $user['id'];
        // 转账金额
        $log['amount'] = $amount;
        // 矿工费用
        $fee = bcmul(Ethereum::hexToBigInteger($gas)->toString(), Ethereum::hexToBigInteger($gasPrice)->toString(), 0);
        $log['fee'] = bcdiv($fee, Utils::UNITS['ether'], 18);
        $log['gas'] = Ethereum::hexToDec($gas);
        $log['gasPrice'] = bcdiv(Ethereum::hexToDec($gasPrice), Utils::UNITS['gwei'], 2);
        $log['address'] = $address;
        $log['hash'] = $hash;

        return $log->save();
    }

    /**
     * 关联用户表
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 分页查询数据
     *
     * @param $lastId
     * @param $page
     * @param array $condition
     * @return array
     */
    public static function pageList($lastId, $page, $condition = [])
    {
        // 创建查询构造器
        $builder = self::query();

        // 是否为后台请求
        if (self::fromAdmin())
        {
            $builder->with(['user' => function($query) {
                $query->select(['id', User::accountType() . ' as account', 'nickname', 'avatar']);
            }])->has('user');
        }

        // 用户 ID
        if (isset($condition['userId']) && $condition['userId'] !== '')
        {
            $builder->where('user_id', intval($condition['userId']));
        }

        // 充值者信息
        if (isset($condition['user']) && $condition['user'] !== '')
        {
            $builder->whereHas('user', function ($query) use ($condition) {
                $query->where('id', intval($condition['user']))
                    ->orWhere(User::accountType(), 'like', "%{$condition['user']}%")
                    ->orWhere('nickname', 'like', "%{$condition['user']}%");
            });
        }

        // 记录时间
        if (isset($condition['timeRange']) && !empty($condition['timeRange']))
        {
            $builder->whereBetween(self::CREATED_AT, $condition['timeRange']);
        }

        if (self::fromAdmin())
        {
            $extras = [];
        }
        else
        {
            $extras = [
                'columns' => ['id', 'amount', 'gas', 'gas_price', 'address', 'hash', 'fee', 'created_at']
            ];
        }

        return self::paginate($builder, $lastId, $page, $extras);
    }
}
