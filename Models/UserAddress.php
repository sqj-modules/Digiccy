<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/23
 * Time: 7:47 下午
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\User;

class UserAddress extends Digiccy
{
    protected static $nonexistent = '钱包地址不存在';

    /**
     * 添加钱包地址
     *
     * @param User $user
     * @param $data
     * @return bool
     */
    public static function insert(User $user, $data)
    {
        // 创建记录
        $record = new UserAddress();

        // 地址拥有者
        $record['userId'] = $user['id'];

        return $record->change($data);
    }

    /**
     * 修改钱包地址
     *
     * @param $data
     * @return bool
     */
    public function change($data)
    {
        // 钱包名称
        if (isset($data['name']))
        {
            $this['name'] = $data['name'];
        }

        // 钱包地址
        if (isset($data['address']))
        {
            $this['address'] = $data['address'];
        }

        // 钱包备注
        if (isset($data['remark']))
        {
            $this['remark'] = $data['remark'];
        }

        return $this->save();
    }

    /**
     * 分页获取地址
     *
     * @param $lastId
     * @param $page
     * @param $condition
     * @return array
     */
    public static function pageList($lastId, $page, $condition)
    {
        // 创建查询构造器
        $builder = self::query();

        // 指定用户
        if (isset($condition['userId']) && $condition['userId'] !== '')
        {
            $builder->where('user_id', intval($condition['userId']));
        }

        return self::paginate($builder, $lastId, $page, [
            'columns' => ['id', 'name', 'address']
        ]);
    }
}
