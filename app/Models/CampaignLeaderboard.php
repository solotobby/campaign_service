<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignLeaderboard extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'audience_id', 'play_durations', 'play_points',
        'referral_points', 'total_points'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}