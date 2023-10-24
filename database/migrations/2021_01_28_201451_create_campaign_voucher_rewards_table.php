<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignVoucherRewardsTable extends Migration
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
            $table->integer("quantity");
            $table->integer("quantity_remainder");
            $table->boolean("assign_to_multiple_users")->default(false);
            $table->boolean("has_pregenerated_voucher")->default(false);
            $table->boolean("generate_on_fly")->default(false);
            $table->string("description")->nullable();
            $table->json('specific_days')->nullable();
            $table->string('icon_url')->nullable();
            $table->enum('redemption_mode', ["ONLINE", "OFFLINE"])->nullable();
            $table->string('redemption_url')->nullable();
            $table->integer('redemption_expiry')->nullable();
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
