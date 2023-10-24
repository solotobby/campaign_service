<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class ArielInfluencer extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name', 'code'
    ];
    
    /**
     * influencerActivities
     *
     * @return void
     */
    public function influencerActivities()
    {
        return $this->hasMany(ArielInfluencerActivity::class, 'ariel_influencer_id');
    }
}