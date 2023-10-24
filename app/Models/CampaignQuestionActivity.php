<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignQuestionActivity extends Model
{
    use UuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id', 'audience_id', 'campaign_question_id', 'point',
        'duration', 'game_play_used', 'campaign_game_play_id', 'choice_id'
    ];

    public function question()
    {
        return $this->belongsTo(CampaignQuestion::class, 'campaign_question_id');
    }
}