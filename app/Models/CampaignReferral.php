<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignReferral extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referrer_id', 'campaign_id', 'code'
    ];
    
    /**
     * referents
     *
     * @return void
     */
    public function referents()
    {
        return $this->hasMany(CampaignReferralActivity::class);
    }
}