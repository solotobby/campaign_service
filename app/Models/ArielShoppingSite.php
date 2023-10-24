<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class ArielShoppingSite extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'icon', 'url'
    ];

    public function influencerActivities()
    {
        return $this->hasMany(ArielInfluencerActivity::class, 'ariel_shopping_site_id');
    } 
}