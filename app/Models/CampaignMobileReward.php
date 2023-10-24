<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignMobileReward extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'type', 'reward', 'quantity', 'quantity_remainder',
        'specific_days', 'icon_url', 'cash_reward_to_wallet', 'cash_reward_to_bank', 'is_redeem'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
