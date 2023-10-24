<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignVoucherRewardActvitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_voucher_reward_activities', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('campaign_id')->index();
            $table->uuid('campaign_voucher_reward_id');
            $table->uuid('audience_id')->index()->nullable();
            $table->string('voucher');
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('redeemed_at')->nullable();
            $table->integer('redemption_duration')->nullable();
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
        Schema::dropIfExists('campaign_voucher_reward_activities');
    }
}
