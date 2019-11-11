<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_contracts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Token
            $table->string('token', 32)
                ->default('')
                ->comment('代币Token');

            // 代币符号
            $table->string('symbol', 32)
                ->default('')
                ->comment('代币的符号')
                ->index();

            // 合约地址
            $table->string('address')
                ->default('')
                ->comment('合约地址');

            // 智能合约ABI
            $table->longText('abi')
                ->comment('只能合约的ABI');

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
        Schema::dropIfExists('digiccy_contracts');
    }
}
