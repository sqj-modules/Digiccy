<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/11
 * Time: 3:51 下午
 */
namespace SQJ\Modules\Digiccy\Models;

use App\Models\UserWithdrawalLog;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;

class TransactionLog extends Digiccy
{
    /**
     * 添加转账记录
     *
     * @param string $model 模型类名
     * @param int $id 模型数据ID
     * @param string $hash 交易哈希
     * @param string $symbol 交易合约
     * @return bool
     */
    public static function insert($model, $id, $hash, $symbol = '')
    {
        $log = new TransactionLog();

        // 数据模型
        $log['transferableType'] = $model;
        // 数据ID
        $log['transferableId'] = $id;
        // 交易HASH
        $log['hash'] = $hash;
        // 交易合约
        $log['symbol'] = $symbol;

        return $log->save();
    }

    /**
     * 查询转账结果
     *
     * @return int
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     * @throws \Caffeinated\Modules\Exceptions\ModuleNotFoundException
     */
    public function queryReceipt()
    {
        // 查询状态
        $status = Ethereum::queryTransactionReceipt($this['hash'], $this['symbol']);

        // 若交易完成，则处理状态
        if ($status != 0)
        {
            // 完成时间
            $this['finishedAt'] = now_datetime();
            // 状态
            $this['status'] = $status;

            // 保存修改
            $this->save();

            // 如果失败，则进行失败处理
            if ($status == -1)
            {
                $this->fail();
            }
        }

        return $status;
    }

    /**
     * 转账失败的处理
     *
     * @throws \App\Exceptions\UserException
     */
    private function fail()
    {
        // 提币失败
        if ($this['transferableType'] == UserWithdrawalLog::class)
        {
            UserWithdrawalLog::getById($this['transferableId'])->reset();
        }
    }
}
