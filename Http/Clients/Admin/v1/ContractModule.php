<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-09-12
 * Time: 10:11
 */
namespace SQJ\Modules\Digiccy\Http\Clients\Admin\v1;

use App\Http\Controllers\Api\ApiModule;
use SQJ\Modules\Digiccy\Models\Contract as ContractModel;

class ContractModule extends ApiModule
{
    protected $name = '合约管理';

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 合约列表
            '1000' => [
                'method' => 'list',
                'permission' => 'digiccy.contract.list',
                'label' => '查看'
            ],
            // 添加合约
            '1001' => [
                'method' => 'add',
                'permission' => 'digiccy.contract.add',
                'label' => '添加'
            ],
            // 合约详情
            '1002' => 'detail',
            // 编辑合约
            '1003' => [
                'method' => 'change',
                'permission' => 'digiccy.contract.edit',
                'label' => '编辑'
            ],
            // 删除合约
            '1004' => [
                'method' => 'delete',
                'permission' => 'digiccy.contract.delete',
                'label' => '删除'
            ]
        ];
    }

    /**
     * 所有权限
     *
     * @return array|bool
     */
    public function permissions()
    {
        return [];
    }

    /**
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    protected function list()
    {
        return $this->pageList(ContractModel::class);
    }

    /**
     * 添加智能合约
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     */
    protected function add($callback)
    {
        // 合约数据
        $data = $this->validateData();

        // 添加合约
        ContractModel::insert($data);

        $callback(___('添加智能合约'));

        return ___('添加智能合约成功');
    }

    /**
     * 合约详情
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

        // 获取合约
        $contract = ContractModel::getById($data['id']);

        return [
            'id' => $contract['id'],
            'token' => $contract['token'],
            'symbol' => $contract['symbol'],
            'address' => $contract['address'],
            'abi' => $contract['abi'],
            'recharges' => $contract['recharges'],
            'withdrawals' => $contract['withdrawals'],
            'isDisabled' => $contract['isDisabled'],
            'isSystem' => $contract['isSystem'] == 1
        ];
    }

    /**
     * 修改智能合约
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function change($callback)
    {
        // 合约数据
        $data = $this->validateData(true);

        // 获取合约
        $contract = ContractModel::getById($data['id']);

        // 修改数据
        $contract->change($data);

        $callback(___('修改智能合约【%id%】', ['%id%' => $data['id']]));

        return ___('修改智能合约成功');
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
        // 是否必须
        $nullable = $isChanging ? 'nullable' : 'required';

        $rules = [
            'token' => "{$nullable}|string",
            'symbol' => "{$nullable}|string",
            'address' => "{$nullable}|string",
            'abi' => "{$nullable}|string",
            'recharges' => "{$nullable}|array",
            'withdrawals' => "{$nullable}|array"
        ];

        if ($isChanging)
        {
            $rules['id'] = 'required|numeric';
        }

        return $this->validate($rules);
    }

    /**
     * 删除合约
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function delete($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取合约
        $contract = ContractModel::getById($data['id']);

        // 删除合约
        $contract->remove();

        $callback(___('删除智能合约【%id%】', ['%id%' => $data['id']]));

        return ___('删除智能合约成功');
    }
}
