<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignVoucherRewards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_voucher_rewards', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('campaign_id')->index();
            $table->string("voucher");
            $table->string("voucher_value");
            $table->uuid('audience_id')->index()->nullable();
            $table->string("description")->nullable();
            $table->timestamp('assigned_at')->nullable();
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
        Schema::dropIfExists('campaign_voucher_rewards');
    }
}
