<?php

namespace SQJ\Modules\Digiccy\Models;

use SQJ\Modules\Digiccy\Events\MarketTickerUpdated;
use SQJ\Modules\Digiccy\Support\Facades\DigiccyMarket;
use Illuminate\Support\Facades\Log;

class Currency extends Digiccy
{
    /**
     * 已上线
     */
    const STATE_ONLINE = 0;

    /**
     * 已下线，不可交易
     */
    const STATE_OFFLINE = 1;

    /**
     * 暂停交易
     */
    const STATE_SUSPEND = 2;

    /**
     * 获取聚合行情
     */
    public static function queryMergedTickers()
    {
        // 获取所有启用的交易对
        $symbolList = self::enabledSymbols();

        $tickerList = [];

        foreach ($symbolList as $item)
        {
            try
            {
                $data = DigiccyMarket::queryMergedTicker($item['symbol']);

                // 名称
                $data['name'] = strtoupper($item['name']);
                // 报价币种
                $data['quote'] = strtoupper($item['quote']);

                $tickerList[] = $data;
            }
            catch (\Exception $exception)
            {
                Log::error($exception->getMessage());
            }
        }

        event(new MarketTickerUpdated($tickerList));

        return $tickerList;
    }

    /**
     * 获取启用的交易对
     *
     * @return mixed
     */
    private static function enabledSymbols()
    {
        return self::cache()->rememberForever('enabled_symbols', function () {
            return self::query()
                ->whereNull('disabled_at')
                ->select(['id', 'name', 'quote', 'symbol'])
                ->get()
                ->toArray();
        });
    }

    /**
     * 币种字典
     *
     * @return array
     */
    public static function dictionary()
    {
        // 获取所有启用的货币
        $enabledList = self::enabledSymbols();

        // 字典数据
        $dictionary = [];

        foreach ($enabledList as $item)
        {
            try
            {
                // 获取聚合行情
                $data = DigiccyMarket::queryMergedTicker($item['symbol']);

                $dictionary[] = [
                    'label' => strtoupper($item['name']),
                    'value' => $item['id'],
                    'rate' => $data['close']
                ];
            }
            catch (\Exception $exception)
            {
                Log::error($exception->getMessage());
            }
        }

        return $dictionary;
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
            ->orderBy('disabled_at');

        // 货币名称
        if (isset($condition['name']) && $condition['name'] !== '')
        {
            $builder->where('name', 'like', "%{$condition['name']}%");
        }

        // 货币状态
        if (isset($condition['isDisabled']) && $condition['isDisabled'] !== '')
        {
            if ($condition['isDisabled'])
            {
                $builder->whereNotNull('disabled_at');
            }
            else
            {
                $builder->whereNull('disabled_at');
            }
        }

        if (self::fromAdmin())
        {
            $builder->selectRaw('*,' . SQJ_SQL_IS_DISABLED);
        }

        return self::paginate($builder, $lastId, $page);
    }

    /**
     * 修改货币
     *
     * @param $data
     * @return bool
     */
    public function change($data)
    {
        if (isset($data['isDisabled']) && $data['isDisabled'] !== '')
        {
            if ($data['isDisabled'])
            {
                $this['disabledAt'] = now_datetime();
            }
            else
            {
                $this['disabledAt'] = null;
            }
        }

        $this->save();

        // 清除缓存
        static::flushCache();
    }
}
