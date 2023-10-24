<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnCampaignAdBreakerActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_ad_breaker_activities', function (Blueprint $table) {
            $table->uuid('campaign_game_play_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_ad_breaker_activities', function (Blueprint $table) {
            $table->dropColumn('campaign_game_play_id');
        });
    }
}
