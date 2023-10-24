<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnGameRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_game_rules', function (Blueprint $table) {
            $table->integer('interval_data_collection')->default(0);
            $table->integer('interval_display_ad')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_game_rules', function (Blueprint $table) {
            $table->dropColumn(['interval_data_collection', 'interval_display_ad']);
        });
    }
}
