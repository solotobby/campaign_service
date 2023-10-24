<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Campaign;
use Illuminate\Support\Facades\Http;
use App\Jobs\ImportOpentDbQuestionsJob;

class ImportQuestionsOpentDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:opentdbImport';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import questions from opentdb';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // return campaign that has a game rule that says import_opentdb_questions
        $campaigns = Campaign::whereDate('start_date', '<=', (Carbon::now())->toDateString())
                            ->whereDate('end_date', '>=', (Carbon::now())->toDateString())
                            ->whereHas('rules', function ($query) {
                                $query->where('import_opentdb_questions', true);
                            })->get();
        if (count($campaigns) == 0) {
            return;
        }
        try {
            foreach ($campaigns as $campaign) {
                // get campaign game rules
                $gameRule = $campaign->rules;
                $numQuestions = $gameRule['max_questions_per_play'] * $gameRule['maximum_game_play'];
                /*
                 *according to opentdb api, 
                 *total number of questions you can pull per request is limited to 50
                 */
                if ($numQuestions > 50) {
                    // divide numQuestions by 50 to get number of time to run this particular queue
                    $times = ceil($numQuestions / 50);
                    for ($i=0; $i <= $times; $i++) { 
                        // push job onto queue
                        $job = (new ImportOpentDbQuestionsJob($campaign->id, 50))->delay(Carbon::now()->addSeconds(30));
                    }
                } else {
                    // push job onto queue
                    $job = (new ImportOpentDbQuestionsJob($campaign->id, $numQuestions))->delay(Carbon::now()->addSeconds(30));
                }
            }
        } catch (\Throwable $th) {
            report($th);
        }
    }
}