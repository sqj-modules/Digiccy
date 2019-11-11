<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2018/11/8
 * Time: 11:21 AM
 */
namespace SQJ\Modules\Digiccy\Support\Contracts\Market;

use Illuminate\Contracts\Events\Dispatcher;

class Repository
{
    private $market;

    private $events;

    public function __construct(Market $market)
    {
        $this->market = $market;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * 获取市场中所有的交易币
     *
     * @return mixed
     */
    public function queryAllCurrencies()
    {
        return $this->market->queryAllCurrencies();
    }

    public function queryAllSymbols()
    {
        return $this->market->queryAllSymbols();
    }

    /**
     * 获取指定交易对的聚合行情
     * @param $symbol
     * @return mixed
     */
    public function queryMergedTicker($symbol)
    {
        return $this->market->queryMergedTicker($symbol);
    }
}
