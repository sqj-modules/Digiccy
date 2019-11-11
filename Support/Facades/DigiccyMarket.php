<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-08-21
 * Time: 21:49
 */
namespace SQJ\Modules\Digiccy\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Market
 *
 * @method static array queryAllCurrencies();
 * @method static array queryAllSymbols();
 * @method static array queryMergedTicker($symbol);
 *
 * @package SQJ\Modules\Digiccy\Support\Facades
 */
class DigiccyMarket extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'DigiccyMarket';
    }
}
