<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyRechargeLogsTable002 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digiccy_recharge_logs', function (Blueprint $table) {

            // 删除外键
            $table->dropForeign(['currency_id']);

            // 删除部分字段
            $table->dropColumn(['currency_id', 'cost', 'rate']);

            // 合约ID
            $table->unsignedBigInteger('contract_id')
                ->default(0)
                ->comment('充币合约的ID')
                ->index();

            // 添加外键约束
            $table->foreign('contract_id')
                ->references('id')
                ->on('digiccy_contracts')
                ->onDelete('cascade');

            // 到账钱包
            $table->string('credit_type', 32)
                ->default('')
                ->comment('充值到账钱包')
                ->index();

            // 到账金额
            $table->decimal('credit', config('app.user_credit_total'), config('app.user_credit_place'))
                ->default(0)
                ->comment('充值实际到账金额');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('digiccy_recharge_logs', function (Blueprint $table) {

            // 充值币种
            $table->unsignedBigInteger('currency_id')
                ->default(0)
                ->comment('充值币种的ID');

            // 充值花费金额
            $table->decimal('cost', 20, 8)
                ->default(0)
                ->comment('实际花费的充值币种数量');

            // 充值时的兑换率
            $table->decimal('rate', 20, 8)
                ->default(0)
                ->comment('充值时的实际兑换率');

            // 添加外键约束
            $table->foreign('currency_id')
                ->references('id')
                ->on('digiccy_currencies')
                ->onDelete('cascade');

            // 删除外键
            $table->dropForeign(['contract_id']);

            // 删除新增字段
            $table->dropColumn(['contract_id', 'credit_type', 'credit']);
        });
    }
}
