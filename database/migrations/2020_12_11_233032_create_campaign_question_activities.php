<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignQuestionActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_question_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_question_id');
            $table->uuid('campaign_id');
            $table->uuid('audience_id');
            $table->integer('point')->default(0);
            $table->double('duration')->default(0);
            $table->integer('game_play_used')->nullable();
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
        Schema::dropIfExists('campaign_question_activities');
    }
}
