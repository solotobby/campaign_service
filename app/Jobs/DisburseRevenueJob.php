<?php

namespace App\Jobs;

class DisburseRevenueJob extends Job
{
    protected $influencerID;
    protected $audienceID;
    protected $campaignID;
    protected $activityID;
    protected $channel;
    protected $revenueType;
    protected $revenue;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($influencerID=null, $audienceID, $campaignID, $activityID, $channel, $revenueType, $revenue=0)
    {
        $this->influencerID = $influencerID;
        $this->audienceID = $audienceID;
        $this->campaignID = $campaignID;
        $this->activityID = $activityID;
        $this->channel = $channel;
        $this->revenueType = $revenueType;
        $this->revenue = $revenue;
        $this->onQueue(env('AWS_SQS_DISBURSEADREVENUE_QUEUE'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // handle on wallet service
    }
}
