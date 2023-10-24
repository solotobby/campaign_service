<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatLeaderboardRewardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_leaderboard_rewards', function (Blueprint $table) {
            $table->integer("player_position")->default(0)->change();
            $table->integer("top_players_start")->default(0);
            $table->integer("top_players_end")->default(0);
            $table->integer("top_players_revenue_share_percent")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_leaderboard_rewards', function (Blueprint $table) {
            $table->dropColumn(['top_players_start', 'top_players_end', 'top_players_revenue_share_percent'])->nullable();
        });
    }
}
