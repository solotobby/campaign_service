<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignMobileRedemptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_mobile_redemptions', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('campaign_id');
            $table->uuid('campaign_mobile_reward_id')->index();
            $table->uuid('audience_id')->index();
            $table->enum('status', ["SUCCESS", "FAILED", 'PENDING']);
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
        Schema::dropIfExists('campaign_mobile_redemptions');
    }
}
