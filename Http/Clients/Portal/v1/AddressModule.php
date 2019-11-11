<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/23
 * Time: 7:37 下午
 */
namespace SQJ\Modules\Digiccy\Http\Clients\Portal\v1;

use App\Http\Controllers\Api\ApiModule;
use SQJ\Modules\Digiccy\Models\UserAddress;

/**
 * @ApiSector (地址簿)
 *
 * Class AddressModule
 * @package SQJ\Modules\Digiccy\Http\Clients\Portal\v1
 */
class AddressModule extends ApiModule
{

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 地址列表
            '1000' => 'getList',
            // 添加地址
            '1001' => 'add',
            // 地址详情
            '1002' => 'detail',
            // 编辑地址
            '1003' => 'edit',
            // 删除地址
            '1004' => 'remove'
        ];
    }

    /**
     * @ApiTitle (地址列表)
     *
     * @ApiParams (name="lastId", type="number", required=true, description="最新地址ID。首次传0，之后传接口返回的lastId")
     * @ApiParams (name="page", type="number", required=true, description="请求数据页码")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="钱包ID")
     * @ApiReturnParams (name="name", group="list", type="string", required=true, description="钱包名称")
     * @ApiReturnParams (name="address", group="list", type="string", required=true, description="钱包地址")
     * @ApiReturnParams (name="remark", group="list", type="string", required=true, description="钱包备注")
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function getList()
    {
        // 当前用户
        $user = $this->user();

        return $this->pageList(UserAddress::class, [
            'userId' => $user['id']
        ]);
    }

    /**
     * @ApiTitle (添加地址)
     *
     * @ApiParams (name="name", type="string", required=true, description="钱包名称。用于区分钱包地址")
     * @ApiParams (name="address", type="string", required=true, description="钱包地址")
     * @ApiParams (name="remark", type="string", required=false, description="备注说明")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     */
    protected function add()
    {
        // 验证数据
        $data = $this->validateData();

        // 当前用户
        $user = $this->user();

        // 添加地址
        UserAddress::insert($user, $data);

        return ___('添加成功');
    }

    /**
     * @ApiTitle (地址详情)
     *
     * @ApiParams (name="id", type="number", required=true, description="地址ID")
     *
     * @ApiReturnParams (name="id", type="number", required=true, description="地址ID")
     * @ApiReturnParams (name="name", type="string", required=true, description="钱包名称。用于区分钱包地址")
     * @ApiReturnParams (name="address", type="string", required=true, description="钱包地址")
     * @ApiReturnParams (name="remark", type="string", required=false, description="备注说明")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function detail()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取地址
        $address = UserAddress::getById($data['id']);

        // 验证地址拥有者
        $this->validateOwner($address);

        return [
            'id' => $address['id'],
            'name' => $address['name'],
            'address' => $address['address'],
            'remark' => $address['remark']
        ];
    }

    /**
     * @ApiTitle (修改地址)
     *
     * @ApiParams (name="id", type="number", required=true, description="地址ID")
     * @ApiParams (name="name", type="string", required=true, description="钱包名称。用于区分钱包地址")
     * @ApiParams (name="address", type="string", required=true, description="钱包地址")
     * @ApiParams (name="remark", type="string", required=false, description="备注说明")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function edit()
    {
        // 验证数据
        $data = $this->validateData(true);

        // 获取地址
        $address = UserAddress::getById($data['id']);

        // 验证地址拥有者
        $this->validateOwner($address);

        // 修改数据
        $address->change($data);

        return ___('修改成功');
    }

    /**
     * @ApiTitle (删除地址)
     *
     * @ApiParams (name="id", type="number", required=true, description="地址ID")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function remove()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取地址
        $address = UserAddress::getById($data['id']);

        // 验证地址拥有者
        $this->validateOwner($address);

        // 删除地址
        $address->delete();

        return ___('删除成功');
    }

    /**
     * 验证数据
     *
     * @param bool $isChanging
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    private function validateData($isChanging = false)
    {
        // 是否为可选值
        $nullable = $isChanging ? 'nullable' : 'required';

        // 验证规则
        $rules = [
            'name' => "{$nullable}|string|min:1",
            'address' => "{$nullable}|string|min:1",
            'remark' => 'nullable|string'
        ];

        // 修改
        if ($isChanging)
        {
            $rules['id'] = 'required|numeric';
        }

        return $this->validate($rules);
    }

    /**
     * 验证地址的拥有者
     *
     * @param UserAddress $address
     * @throws \App\Exceptions\DeveloperException
     */
    private function validateOwner(UserAddress $address)
    {
        // 当前用户
        $user = $this->user();

        if ($user['id'] != $address['userId'])
        {
            throw_developer(___('当前用户与钱包地址拥有者不匹配'));
        }
    }
}
