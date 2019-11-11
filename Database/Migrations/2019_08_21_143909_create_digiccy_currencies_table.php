<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_currencies', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 币种接口中的名称
            $table->string('name', 32)
                ->default('')
                ->comment('币种接口中使用的名字');

            // 报价币种
            $table->string('quote', 32)
                ->default('')
                ->comment('报价的币种');

            // 币种符号
            $table->string('symbol', 32)
                ->default('')
                ->comment('币种符号');

            // 交易对状态
            $table->tinyInteger('state')
                ->default(0)
                ->comment('交易状态。0：已上线；1：已下线，不可交易；2：暂停交易');

            // 禁用时间
            $table->timestamp('disabled_at')
                ->nullable()
                ->comment('禁用时间');

            // 软删除
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
        Schema::dropIfExists('digiccy_currencies');
    }
}
