<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignGameRule extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['campaign_id', 'leaderboard_num_winners', 'cut_off_mark', 
        'maximum_game_play', 'maximum_win', 'is_data_collection', 'is_subscription_based', 
        'has_free_game_play', 'num_free_game_plays', 'has_referral', 'referral_points',
        'has_ad_breaker', 'has_leaderboard', 'duration_per_game_play', 'interval_data_collection', 
        'interval_display_ad', 'max_questions_per_play', 'is_pay_as_you_go', 'pay_as_you_go_amount',
        'payout', 'import_opentdb_questions'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
