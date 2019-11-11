<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyContractsTable003 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digiccy_contracts', function (Blueprint $table) {

            // 删除提币率字段
            $table->dropColumn(['withdrawal_rate']);

            // 充币参数
            $table->json('recharges')
                ->comment('充币的比例');

            // 提币参数
            $table->json('withdrawals')
                ->comment('提币的比例');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('digiccy_contracts', function (Blueprint $table) {

            // 添加兑换比例
            $table->decimal('withdrawal_rate', config('digiccy.currency_total'), config('digiccy.currency_place'))
                ->default(1)
                ->comment('提币时的兑换比例');

            $table->dropColumn(['recharges', 'withdrawals']);
        });
    }
}
