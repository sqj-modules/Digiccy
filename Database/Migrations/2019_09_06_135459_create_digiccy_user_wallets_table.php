<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDigiccyUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_user_wallets', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 用户ID
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('用户的ID')
                ->index();

            // 钱包地址
            $table->string('address', 64)
                ->default('')
                ->comment('钱包地址');

            // 钱包私钥
            $table->string('private_key', 128)
                ->default('')
                ->comment('钱包的用户私钥');

            // 钱包二维码
            $table->string('qr_code')
                ->default('')
                ->comment('钱包二维码');

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
        Schema::dropIfExists('digiccy_user_wallets');
    }
}
