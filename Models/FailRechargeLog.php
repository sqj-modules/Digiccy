<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-10
 * Time: 17:50
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\User;

class FailRechargeLog  extends Digiccy
{
    /**
     * 添加失败记录
     *
     * @param User $user
     * @param $hash
     * @param $reason
     * @param $type
     * @return bool
     */
    public static function insert(User $user, $hash, $reason, $type)
    {
        $log = new FailRechargeLog();

        // 用户
        $log['userId'] = $user['id'];
        // 交易 HASH
        $log['hash'] = $hash;
        // 失败原因
        $log['failedReason'] = $reason;
        // 失败类型
        $log['type'] = $type;
        // 状态
        $log['status'] = 0;

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
        $builder = self::query()->where('status', 0);

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

        // 失败类型
        if (isset($condition['type']) && $condition['type'] !== '')
        {
            $builder->where('type', intval($condition['type']));
        }

        // 记录时间
        if (isset($condition['timeRange']) && !empty($condition['timeRange']))
        {
            $builder->whereBetween(self::CREATED_AT, $condition['timeRange']);
        }

        return self::paginate($builder, $lastId, $page);
    }
}
