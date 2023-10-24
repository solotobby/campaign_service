<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArielInfluencerActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ariel_influencer_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ariel_influencer_id');
            $table->uuid('ariel_shopping_site_id');
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
        Schema::dropIfExists('ariel_influencer_activities');
    }
}
