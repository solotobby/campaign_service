<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CampaignSpinWheelReward extends Model
{
    use UuidTrait;

    protected $table = "campaign_spinwheel_rewards";

    protected $fillable = ['campaign_id', 'audience_id', 'type', 'value', 'status', 'is_redeem'];

}
