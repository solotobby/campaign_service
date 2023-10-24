<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignAdBreaker extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'on_question_num', 'asset_url', 'action_url'
    ];

    public function activities()
    {
        return $this->hasMany(CampaignAdBreakerActivity::class);
    }
}