<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Http;
use App\Models\CampaignLeaderboardRedemption;

class AirtimeJob extends Job
{
    protected $phone;
    protected $callBackUrl;
    protected $amount;
    protected $medium;
    protected $audienceId;
    protected $applicationName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone, $amount, $callBackUrl, $medium, $audienceId, $applicationName)
    {
        $this->phone = $phone;
        $this->amount = $amount;
        $this->callBackUrl = $callBackUrl;
        $this->medium = $medium;
        $this->audienceId = $audienceId;
        $this->applicationName = $applicationName;
        $this->onQueue(env('AWS_SQS_AIRTIME_QUEUE'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // serverless microservice will handle this
    }
}
