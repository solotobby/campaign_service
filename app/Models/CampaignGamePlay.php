<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignGamePlay extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'audience_id', 'campaign_subscription_id',
        'durations', 'points', 'referrer_id', 'paused_at',
        'campaign_game_id', 'time'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function questionActivities()
    {
        return $this->hasMany(CampaignQuestionActivity::class);
    }
}
