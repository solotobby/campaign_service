<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignAdBreakerActivity extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_ad_breaker_id', 'campaign_id', 'audience_id', 'activity', 'campaign_game_play_id'
    ];
}