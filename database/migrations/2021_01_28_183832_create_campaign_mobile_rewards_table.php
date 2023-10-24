<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignMobileRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_mobile_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id')->index();
            $table->enum('type', ["AIRTIME", "CASH", "DATA"]);
            $table->decimal("reward", 8, 2);
            $table->integer("quantity");
            $table->integer("quantity_remainder");
            $table->json('specific_days')->nullable();
            $table->string('icon_url')->nullable();
            $table->boolean('cash_reward_to_wallet')->default(false);
            $table->boolean('cash_reward_to_bank')->default(false);
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
        Schema::dropIfExists('campaign_mobile_rewards');
    }
}
