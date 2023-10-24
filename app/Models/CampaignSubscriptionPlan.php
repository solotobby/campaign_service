<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignSubscriptionPlan extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['campaign_id', 'title', 'price', 'game_plays'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}