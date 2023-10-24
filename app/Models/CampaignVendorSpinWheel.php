<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class CampaignVendorSpinWheel extends Model
{

    use UuidTrait;

    protected $table = "campaign_vendor_spinwheel";

    protected $fillable = ['name', 'email', 'phone', 'status'];

}
