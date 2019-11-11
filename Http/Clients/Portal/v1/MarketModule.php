<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 20:43
 */
namespace SQJ\Modules\Digiccy\Http\Clients\Portal\v1;

use App\Http\Controllers\Api\ApiModule;
use SQJ\Modules\Digiccy\Models\Currency;

/**
 * @ApiSector (货币市场)
 *
 * Class MarketModule
 * @package SQJ\Modules\Digiccy\Http\Clients\Portal\v1
 */
class MarketModule extends ApiModule
{

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            '1000' => 'mergedTicker'
        ];
    }

    /**
     * @ApiTitle (市场行情)
     *
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="货币列表")
     *
     * @ApiReturnParams (name="name", group="list", type="string", required=true, description="基础币种名称")
     * @ApiReturnParams (name="quote", group="list", type="string", required=true, description="报价币种名称")
     * @ApiReturnParams (name="floatRate", group="list", type="number", required=true, description="涨跌幅")
     * @ApiReturnParams (name="open", group="list", type="number", required=true, description="本阶段开盘价")
     * @ApiReturnParams (name="openCNY", group="list", type="number", required=true, description="本阶段开盘价[人民币]")
     * @ApiReturnParams (name="close", group="list", type="number", required=true, description="本阶段最新价")
     * @ApiReturnParams (name="closeCNY", group="list", type="number", required=true, description="本阶段最新价[人民币]")
     * @ApiReturnParams (name="low", group="list", type="number", required=true, description="本阶段最低价")
     * @ApiReturnParams (name="lowCNY", group="list", type="number", required=true, description="本阶段最低价[人民币]")
     * @ApiReturnParams (name="high", group="list", type="number", required=true, description="本阶段最高价")
     * @ApiReturnParams (name="highCNY", group="list", type="number", required=true, description="本阶段最高价[人民币]")
     * @ApiReturnParams (name="count", group="list", type="number", required=true, description="交易次数")
     * @ApiReturnParams (name="amount", group="list", type="number", required=true, description="基础币种交易量")
     * @ApiReturnParams (name="volume", group="list", type="number", required=true, description="报价币种交易量")
     *
     * @return array
     */
    protected function mergedTicker()
    {
        return [
            'list' => Currency::queryMergedTickers()
        ];
    }
}
