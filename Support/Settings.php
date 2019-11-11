<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-25
 * Time: 16:56
 */
namespace SQJ\Modules\Digiccy\Support;

use App\Models\SystemConfig;
use SQJ\Modules\Digiccy\Support\Api\Ethereum;
use Web3\Utils;

class Settings
{
    /**
     * 钱包参数
     */
    const WALLET_PARAMS = 'wallet_params';

    /**
     * 区块参数
     */
    const BLOCK_PARAMS = 'block_params';

    /**
     * 提币参数
     */
    const WITHDRAWAL_PARAMS = 'withdrawal_params';

    /**
     * 获取设置参数
     *
     * @param $name
     * @return mixed
     */
    public static function get($name)
    {
        return SystemConfig::get("digiccy_{$name}");
    }

    /**
     * 设置参数
     *
     * @param $name
     * @param $params
     */
    public static function set($name, $params)
    {
        SystemConfig::set("digiccy_{$name}", $params);
    }

    /**
     * 获取 Block Number
     *
     * @return mixed
     */
    public static function getBlockNumber()
    {
        $params = self::get(self::BLOCK_PARAMS);

        if (empty($params) || !isset($params['blockNumber']))
        {
            $params['blockNumber'] = Ethereum::blockNumber();

            self::set(self::BLOCK_PARAMS, $params);
        }

        return $params['blockNumber'];
    }

    /**
     * 设置 区块高度
     *
     * @param $blockNumber
     */
    public static function setBlockNumber($blockNumber)
    {
        $params = self::get(self::BLOCK_PARAMS);

        $params['blockNumber'] = $blockNumber;

        self::set(self::BLOCK_PARAMS, $params);
    }
}
