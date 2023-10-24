<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGameRulesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_game_rules', function (Blueprint $table) {
            $table->integer('max_questions_per_play')->default(0);
            $table->boolean('is_pay_as_you_go')->default(false);
            $table->decimal('pay_as_you_go_amount', 8,2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_game_rules', function (Blueprint $table) {
            $table->dropColumn(['max_questions_per_play', 'is_pay_as_you_go', 'pay_as_you_go_amount']);
        });
    }
}
