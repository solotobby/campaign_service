<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToGreenCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('green_cards', function (Blueprint $table) {
            $table->boolean('status')->default(true);
            $table->boolean('open_to_pool')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('green_cards', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('open_to_pool');
        });
    }
}
