<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Http;

class SmsJob extends Job
{
    protected $phone;
    protected $message;
    protected $route;
    protected $medium;
    protected $senderId;
    protected $audienceId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone, $message, $route, $medium, $senderId, $audienceId)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->route = $route;
        $this->medium = $medium;
        $this->senderId = $senderId;
        $this->audienceId = $audienceId;
        $this->onQueue(env('AWS_SQS_SMS_QUEUE'));
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
