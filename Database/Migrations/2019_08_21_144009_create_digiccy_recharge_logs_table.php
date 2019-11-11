<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyRechargeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_recharge_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 用户ID
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('申请充值的用户ID')
                ->index();

            // 充值钱包地址
            $table->string('address', 128)
                ->default('')
                ->comment('充值钱包地址');

            // 充值币种
            $table->unsignedBigInteger('currency_id')
                ->default(0)
                ->comment('充值币种的ID');

            // 充值到账金额
            $table->decimal('amount', 20, 2)
                ->default(0)
                ->comment('充值币种的数量');

            // 充值花费金额
            $table->decimal('cost', 20, 8)
                ->default(0)
                ->comment('实际花费的充值币种数量');

            // 充值时的兑换率
            $table->decimal('rate', 20, 8)
                ->default(0)
                ->comment('充值时的实际兑换率');

            // 充值状态
            $table->tinyInteger('status')
                ->default(0)
                ->comment('充值状态。0：申请中；1：已通过；-1：已驳回')
                ->index();

            // 充值通过时间
            $table->timestamp('accepted_at')
                ->nullable()
                ->comment('充值通过时间');

            // 充值拒绝时间
            $table->timestamp('rejected_at')
                ->nullable()
                ->comment('充值拒绝时间');

            // 充值拒绝原因
            $table->string('rejected_reason')
                ->default('')
                ->comment('充值拒绝原因');

            // 软删除
            $table->softDeletes();
            $table->timestamps();

            // 添加外键约束
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // 添加外键约束
            $table->foreign('currency_id')
                ->references('id')
                ->on('digiccy_currencies')
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
        Schema::dropIfExists('digiccy_recharge_logs');
    }
}
