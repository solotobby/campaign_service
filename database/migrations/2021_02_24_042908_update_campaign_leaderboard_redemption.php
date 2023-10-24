<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCampaignLeaderboardRedemption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_leaderboard_redemptions', function (Blueprint $table) {
            $table->string('cash_payment_ref')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_leaderboard_redemptions', function (Blueprint $table) {
            $table->dropColumn('cash_payment_ref');
        });
    }
}
