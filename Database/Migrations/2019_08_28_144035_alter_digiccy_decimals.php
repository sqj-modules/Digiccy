<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyDecimals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 总长度
        $total = config('app.user_credit_total');
        // 小数位
        $place = config('app.user_credit_place');

        // 总长度
        $digiccyTotal = config('digiccy.currency_total');
        // 小数位
        $digiccyPlace = config('digiccy.currency_place');

        // 充币记录表
        Schema::table('digiccy_recharge_logs', function (Blueprint $table) use ($total, $place, $digiccyTotal, $digiccyPlace) {

            // 充值到账金额
            $table->decimal('amount', $total, $place)->change();

            // 充值花费金额
            $table->decimal('cost', $digiccyTotal, $digiccyPlace)->change();

            // 充值时的兑换率
            $table->decimal('rate', $digiccyTotal, $digiccyPlace)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 充币记录表
        Schema::table('digiccy_recharge_logs', function (Blueprint $table) {

            // 充值到账金额
            $table->decimal('amount', 12)->change();

            // 充值花费金额
            $table->decimal('cost', 20, 8)->change();

            // 充值时的兑换率
            $table->decimal('rate', 20, 8)->change();
        });
    }
}
