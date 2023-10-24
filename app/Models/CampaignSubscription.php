<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignSubscription extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['campaign_id', 'audience_id', 'campaign_subscription_plan_id', 
        'payment_reference', 'allocated_game_plays', 'available_game_plays'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}