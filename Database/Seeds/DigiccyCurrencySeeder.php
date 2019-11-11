<?php

namespace SQJ\Modules\Digiccy\Database\Seeds;

use SQJ\Modules\Digiccy\Models\Currency;
use SQJ\Modules\Digiccy\Support\Facades\DigiccyMarket;
use Illuminate\Database\Seeder;

class DigiccyCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 获取所有的币种
        $currencyList = DigiccyMarket::queryAllSymbols();

        if (is_array($currencyList))
        {
            foreach ($currencyList as $currency)
            {
                Currency::updateOrCreate([
                    'name' => $currency['base'],
                    'quote' => $currency['quote']
                ], [
                    'symbol' => $currency['symbol'],
                    'disabled_at' => now_datetime()
                ]);
            }
        }
    }
}
