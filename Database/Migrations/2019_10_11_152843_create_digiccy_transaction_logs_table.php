<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyTransactionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_transaction_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 交易合约
            $table->string('symbol')
                ->default('')
                ->comment('交易合约');

            // 交易的HASH
            $table->string('hash')
                ->default('')
                ->comment('交易哈希');

            // 可交易模型
            $table->string('transferable_type')
                ->default('')
                ->comment('可交易模型类名');

            // 可交易模型ID
            $table->unsignedBigInteger('transferable_id')
                ->default(0)
                ->comment('可交易模型对应的ID')
                ->index();

            // 完成时间
            $table->timestamp('finished_at')
                ->nullable()
                ->comment('交易状态查询成功的时间');

            // 交易状态
            $table->tinyInteger('status')
                ->default(0)
                ->comment('交易状态。0：交易中；1：交易成功；-1：交易失败')
                ->index();

            // 开启软删除
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('digiccy_transaction_logs');
    }
}
