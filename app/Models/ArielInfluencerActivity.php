<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class ArielInfluencerActivity extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ariel_influencer_id', 'ariel_shopping_site_id'
    ];

    public function influencer()
    {
        return $this->hasMany(ArielShoppingSite::class);
    }
}