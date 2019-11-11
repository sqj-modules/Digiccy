<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyAutoRechargeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_auto_recharge_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 用户ID
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('用户ID')
                ->index();

            // 总位数
            $total = config('digiccy.currency_total');

            // 小数位数
            $place = config('digiccy.currency_place');

            // 转账金额
            $table->decimal('amount', $total, $place)
                ->default(0)
                ->comment('用户转账金额');

            // Gas
            $table->decimal('gas', $total, $place)
                ->default(0)
                ->comment('用户转账时的gas');

            // GasPrice
            $table->decimal('gas_price', $total, $place)
                ->default(0)
                ->comment('用户转账时的gas_price');

            // 转账地址
            $table->string('address', 64)
                ->default('')
                ->comment('转账地址');

            // 转账时的HASH
            $table->string('hash')
                ->default('')
                ->comment('转账时的hash');

            // 软删除
            $table->softDeletes();
            $table->timestamps();

            // 添加外键约束
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('digiccy_auto_recharge_logs');
    }
}
