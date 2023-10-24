<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignReferralActivity extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referent_id', 'campaign_id', 'campaign_referral_id', 'is_activated',
        'is_activation_point_redeemed'
    ];

    public function Referral()
    {
        return $this->belongsTo(CampaignReferral::class, 'campaign_referral_id', 'id');
    }
}