<?php

namespace App\Jobs;
use App\Models\GreenCard;
use Illuminate\Support\Facades\Http;
use App\Models\CampaignLeaderboardRedemption;

class GreenCardJob extends Job
{


    public  function __construct()
    {

    }

    public function handle()
    {
        //GreenCard::whereNotIn('audience_id', [$getAllSubscriptionInThisMonth['audience_id']])->get();

    }
}
