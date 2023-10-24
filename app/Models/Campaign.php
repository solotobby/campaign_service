<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class Campaign extends Model
{
    use UuidTrait;

    protected $with = ['rules', 'subscriptionPlans', 'adBreakers', 'games', 'leaderboardRewards'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['type', 'title', 'client_id', 'brand_id', 'company_id',
        'start_date', 'end_date', 'status', 'daily_ads_budget', 'total_ads_budget',
        'total_rewards_budget', 'overall_campaign_budget', 'daily_start', 'daily_stop', 'vendor_id'];

    public function rules()
    {
        return $this->hasOne(CampaignGameRule::class);
    }

    public function subscriptionPlans()
    {
        return $this->hasMany(CampaignSubscriptionPlan::class);
    }

    public function questions()
    {
        return $this->hasMany(CampaignQuestion::class);
    }

    public function adBreakers()
    {
        return $this->hasMany(CampaignAdBreaker::class);
    }

    public function games()
    {
        return $this->hasMany(CampaignGame::class);
    }

    public function leaderboardRewards()
    {
        return $this->hasMany(CampaignLeaderboardReward::class);
    }

    public function mobileRewards()
    {
        return $this->hasMany(CampaignMobileReward::class);
    }
}
