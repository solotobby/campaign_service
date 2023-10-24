<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignQuestion extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['campaign_id', 'question_id', 'is_data_collection'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function questionActivities()
    {
        return $this->hasMany(CampaignQuestionActivity::class);
    }
}