<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGamePlayDurationToCampaignGameRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_game_rules', function (Blueprint $table) {
            $table->integer('duration_per_game_play')->nullable();
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
            $table->dropColumn('duration_per_game_play');
        });
    }
}
