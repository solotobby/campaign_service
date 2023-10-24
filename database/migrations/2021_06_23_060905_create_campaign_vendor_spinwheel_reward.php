<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignVendorSpinwheelReward extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_vendor_spinwheel_reward', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('audience_id');
            $table->string('vendor_id');
            $table->string('type');
            $table->string('value');
            $table->string('status')->nullable();
            $table->boolean('is_redeem')->default(false);
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
        Schema::dropIfExists('campaign_vendor_spinwheel_reward');
    }
}
