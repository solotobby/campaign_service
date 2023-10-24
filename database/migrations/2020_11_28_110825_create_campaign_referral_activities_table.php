<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignReferralActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_referral_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('referent_id');
            $table->uuid('campaign_id');
            $table->uuid('campaign_referral_id');
            $table->boolean('is_activated')->default(false);
            $table->boolean('is_activation_point_redeemed')->default(false);
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
        Schema::dropIfExists('campaign_referral_activities');
    }
}
