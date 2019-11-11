<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDigiccyUserAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('digiccy_user_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 用户ID
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('用户ID')
                ->index();

            // 用户姓名
            $table->string('name', 32)
                ->default('')
                ->comment('钱包名称');

            // 钱包地址
            $table->string('address')
                ->default('')
                ->comment('钱包地址');

            // 备注信息
            $table->string('remark')
                ->default('')
                ->comment('备注信息');

            // 软删除
            $table->softDeletes();
            $table->timestamps();

            // 用户字段添加外键约束
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
        Schema::dropIfExists('digiccy_user_addresses');
    }
}
