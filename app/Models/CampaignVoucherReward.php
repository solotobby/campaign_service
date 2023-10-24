<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignVoucherReward extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'audience_id', 'voucher', 'voucher_value',
        'description', 'assigned_at'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}