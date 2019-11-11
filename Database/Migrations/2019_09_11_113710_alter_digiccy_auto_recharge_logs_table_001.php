<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyAutoRechargeLogsTable001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digiccy_auto_recharge_logs', function (Blueprint $table) {

            // 总位数
            $total = config('digiccy.currency_total');

            // 小数位数
            $place = config('digiccy.currency_place');

            // 旷工费用
            $table->decimal('fee', $total, $place)
                ->default(0)
                ->comment('矿工费用，单位ETH');

            // 天然气
            $table->integer('gas')
                ->default(0)
                ->comment('花费的天然气')
                ->change();

            // 天然气价格
            $table->decimal('gas_price')
                ->default(0)
                ->comment('天然气单价，单位：gwei。')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('digiccy_auto_recharge_logs', function (Blueprint $table) {

            // 删除字段
            $table->dropColumn(['fee']);

            // 总位数
            $total = config('digiccy.currency_total');

            // 小数位数
            $place = config('digiccy.currency_place');

            // Gas
            $table->decimal('gas', $total, $place)
                ->default(0)
                ->comment('用户转账时的gas')
                ->change();

            // GasPrice
            $table->decimal('gas_price', $total, $place)
                ->default(0)
                ->comment('用户转账时的gas_price')
                ->change();
        });
    }
}
