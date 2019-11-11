<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 22:14
 */
namespace SQJ\Modules\Digiccy\Support\Contracts\Market;

use SQJ\Modules\Digiccy\Models\Currency;
use SQJ\Modules\Digiccy\Support\Api\HuoBi;
use App\Utils\JuHe;
use Illuminate\Support\Str;

class HuoBiMarket implements Market
{

    public function __construct($config)
    {
    }

    /**
     * 获取市场中所有的交易币
     *
     * @return mixed
     */
    public function queryAllCurrencies()
    {
        return [];
    }

    /**
     * 获取指定交易对的聚合行情
     * @param $symbol
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    public function queryMergedTicker($symbol)
    {
        // 接口返回的数据
        $result = HuoBi::mergedTicker($symbol);

        // 涨跌幅
        $floatRate = formatted_number(($result['close'] - $result['open']) / $result['open'] * 100.0);

        // 汇率
        $cnyRate = $this->exchangeCurrencyRate($symbol);

        return [
            'open' => $result['open'],
            'openCNY' => $result['open'] * $cnyRate,
            'close' => $result['close'],
            'closeCNY' => $result['close'] * $cnyRate,
            'high' => $result['high'],
            'highCNY' => $result['high'] * $cnyRate,
            'low' => $result['low'],
            'lowCNY' => $result['low'] * $cnyRate,
            'count' => $result['count'],
            'amount' => $result['amount'],
            'volume' => $result['vol'],
            'floatRate' => $floatRate
        ];
    }

    /**
     * 获取所有的交易对
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    public function queryAllSymbols()
    {
        $result = HuoBi::allSymbols();

        // 最终的交易对
        $symbolList = [];

        // 报价币种
        $quoteCurrency = config('digiccy.quoteCurrency');

        foreach ($result as $item)
        {
            if (!empty($quoteCurrency) && !in_array($item['quote-currency'], $quoteCurrency))
            {
                continue;
            }

            switch ($item['state'])
            {
                case 'suspend':
                    $state = Currency::STATE_SUSPEND;
                    break;
                case 'offline':
                    $state = Currency::STATE_OFFLINE;
                    break;
                default:
                    $state = Currency::STATE_ONLINE;
                    break;
            }

            $symbolList[] = [
                'base' => $item['base-currency'],
                'quote' => $item['quote-currency'],
                'symbol' => $item['symbol'],
                'state' => $state
            ];
        }

        return $symbolList;
    }

    /**
     * 货币换算
     *
     * @param $amount
     * @param $symbol
     * @return float|int
     * @throws \App\Exceptions\DeveloperException
     */
    private function exchangeCurrencyRate($symbol)
    {
        if (Str::endsWith($symbol, 'usdt'))
        {
            return JuHe::exchangeCurrencyRate('USD', 'CNY');
        }

        return 1;
    }
}
