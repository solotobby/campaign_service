<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnGameplayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_game_plays', function (Blueprint $table) {
            $table->uuid('campaign_subscription_id')->nullable()->change();
            $table->uuid('referrer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_game_plays', function (Blueprint $table) {
            $table->dropColumn('referrer_id');
        });
    }
}
