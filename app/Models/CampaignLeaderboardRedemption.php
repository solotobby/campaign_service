<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignLeaderboardRedemption extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'campaign_leaderboard_reward_id', 'audience_id', 'status',
        'cash_payment_ref', 'reward_value'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignLeaderboardReward()
    {
        return $this->belongsTo(CampaignLeaderboardReward::class);
    }
}