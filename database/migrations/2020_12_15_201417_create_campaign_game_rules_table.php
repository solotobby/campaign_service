<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignGameRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_game_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('campaign_id')->index();
            $table->boolean('has_leaderboard')->default(false);
            $table->integer('leaderboard_num_winners')->default(0);
            $table->integer('cut_off_mark')->default(0);
            $table->integer('maximum_game_play')->default(1);
            $table->integer('maximum_win')->default(1);
            $table->boolean('is_data_collection')->default(false);
            $table->boolean('has_free_game_play')->default(false);
            $table->integer('num_free_game_plays')->default(0);
            $table->boolean('has_referral')->default(false);
            $table->integer('referral_points')->default(0);
            $table->boolean('is_subscription_based')->default(false);
            $table->boolean('has_ad_breaker')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_game_rules');
    }
}
