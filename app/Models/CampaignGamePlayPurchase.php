<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignGamePlayPurchase extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'audience_id', 'campaign_id', 'total_purchased', 'total_remaining', 'total_consumed'
    ];
}