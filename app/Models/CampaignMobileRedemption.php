<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignMobileRedemption extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'campaign_mobile_reward_id', 'audience_id', 'status'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignMobileReward()
    {
        return $this->belongsTo(CampaignMobileReward::class);
    }
}