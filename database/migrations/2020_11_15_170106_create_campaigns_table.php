<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['ADVERT', 'SURVERY', 'TOPBRAIN'])->index();
            $table->string('title');
            $table->time('daily_start')->default('00:00:00');
            $table->time('daily_stop')->default('11:59:59');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('daily_ads_budget', 8, 2)->default(0);
            $table->decimal('total_ads_budget', 8, 2)->default(0);
            $table->decimal('total_rewards_budget', 8, 2)->default(0);
            $table->decimal('overall_campaign_budget', 8, 2)->default(0);
            $table->uuid('brand_id');
            $table->uuid('client_id');
            $table->uuid('company_id');
            $table->enum('status', ['CREATED', 'SUBMITTED', 'ACTIVE', 'PAUSED', 'APPROVED', 'REJECTED', 'COMPLETED'])->default('CREATED')->index();
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
        Schema::dropIfExists('campaigns');
    }
}
