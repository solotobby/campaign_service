<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class CampaignVendorSpinWheelReward extends Model
{

    use UuidTrait;

    protected $table = "campaign_vendor_spinwheel_reward";

    protected $fillable = ['vendor_id', 'audience_id', 'type', 'value', 'status', 'is_redeem'];

}
