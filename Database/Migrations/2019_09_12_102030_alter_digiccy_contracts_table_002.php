<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDigiccyContractsTable002 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digiccy_contracts', function (Blueprint $table) {

            // 是否为系统
            $table->tinyInteger('is_system')
                ->default(0)
                ->comment('是否为系统默认，系统默认无法编辑')
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
        Schema::table('digiccy_contracts', function (Blueprint $table) {

            $table->dropColumn(['is_system']);
        });
    }
}
