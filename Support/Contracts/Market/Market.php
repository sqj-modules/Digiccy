<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2018/11/8
 * Time: 11:21 AM
 */
namespace SQJ\Modules\Digiccy\Support\Contracts\Market;

interface Market
{
    /**
     * 获取市场中所有的交易币
     *
     * @return mixed
     */
    public function queryAllCurrencies();

    /**
     * 获取所有的交易对
     *
     * @return mixed
     */
    public function queryAllSymbols();

    /**
     * 获取指定交易对的聚合行情
     * @param $symbol
     * @return mixed
     */
    public function queryMergedTicker($symbol);
}
