<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignQuestion;

class CampaignQuestionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    /**
     * index
     *
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function index($campaign_id)
    {
        try {
            // get the company where user_id belongs and query campaigns table
            // we could also check the permissions and access level of the user before returning campaigns
            $questions = CampaignQuestion::where('campaign_id', $campaign_id)->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Questions', 'data' =>  $questions], 200);
    }
    
    /**
     * storeInformation
     *
     * @param  mixed $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'ids' => 'required|array'
        ]);

        try {
            $campaign_questions = \DB::transaction(function () use ($validated, $campaign_id) {
                $result = [];
                foreach ($validated['ids'] as $question) {
                    $campaign_question = CampaignQuestion::updateOrCreate([
                        'campaign_id' => $campaign_id,
                        'question_id' => $question['question_id']
                    ], [
                        'campaign_id' => $campaign_id,
                        'question_id' => $question['question_id'],
                        'is_data_collection' => $question['is_data_collection']
                    ]);
                    array_push($result, $campaign_question);
                }
                return $result;
            });
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Questions', 'data' =>  $campaign_questions], 201);
    }
}
