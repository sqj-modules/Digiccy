<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyFailRechargeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_fail_recharge_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 用户ID
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('用户ID')
                ->index();

            // 交易hash
            $table->string('hash')
                ->default('')
                ->comment('交易的hash')
                ->index();

            // 交易类型
            $table->tinyInteger('type')
                ->default(0)
                ->comment('1：待充值的交易；2：自动充值的交易')
                ->index();

            // 交易状态
            $table->tinyInteger('status')
                ->default(0)
                ->comment('0：记录中；1：自动处理成功；-1：自动处理失败');

            // 成功时间
            $table->timestamp('succeeded_at')
                ->nullable()
                ->comment('自动处理成功时间');

            // 失败时间
            $table->timestamp('failed_at')
                ->nullable()
                ->comment('自动处理失败时间');

            // 失败原因
            $table->string('failed_reason')
                ->default('')
                ->comment('失败的原因');

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
        Schema::dropIfExists('digiccy_fail_recharge_logs');
    }
}
