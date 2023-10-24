<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignAdBreakerActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_ad_breaker_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_ad_breaker_id')->index();
            $table->uuid('campaign_id')->index();
            $table->uuid('audience_id')->index();
            $table->enum('activity', ['VIEWED', 'ACTION_CLICKED']);
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
        Schema::dropIfExists('campaign_ad_breaker_activities');
    }
}
