<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyRechargeLogsTable001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digiccy_recharge_logs', function (Blueprint $table) {
            // 添加操作员ID
            $table->unsignedBigInteger('admin_id')
                ->default(0)
                ->comment('操作员ID')
                ->index();
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
            $table->dropColumn(['admin_id']);
        });
    }
}
