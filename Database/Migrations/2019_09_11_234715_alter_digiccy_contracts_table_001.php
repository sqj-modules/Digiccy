<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyContractsTable001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digiccy_contracts', function (Blueprint $table) {

            // 添加兑换比例
            $table->decimal('withdrawal_rate', config('digiccy.currency_total'), config('digiccy.currency_place'))
                ->default(1)
                ->comment('提币时的兑换比例');

            // 禁用时间
            $table->timestamp('disabled_at')
                ->nullable()
                ->comment('禁用时间');
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
            $table->dropColumn(['withdrawal_rate', 'disabled_at']);
        });
    }
}
