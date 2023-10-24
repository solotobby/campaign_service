<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameplayCampaignQuestionAcitivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_question_activities', function (Blueprint $table) {
            $table->uuid('campaign_game_play_id')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_question_activities', function (Blueprint $table) {
            $table->dropColumn('campaign_game_play_id');
        });
    }
}
