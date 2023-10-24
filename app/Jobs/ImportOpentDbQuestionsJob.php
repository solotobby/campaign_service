<?php

namespace App\Jobs;

class ImportOpentDbQuestionsJob extends Job
{
    protected $campaignID;
    protected $numQuestions;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($campaignID, $numQuestions=30)
    {
        $this->campaignID = $campaignID;
        $this->numQuestions = $numQuestions;
        $this->onQueue(env('AWS_SQS_IMPORT_QUESTIONS_QUEUE'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // This Job will be handled on Questions microservice
    }
}
