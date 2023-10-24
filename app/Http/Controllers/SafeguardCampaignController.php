<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignGamePlay;
use App\Models\CampaignSubscription;
use App\Models\CampaignQuestionActivity;
use App\Models\CampaignAdBreakerActivity;
use App\Models\CampaignQuestion;
use App\Models\CampaignGameRule;
use App\Models\Campaign;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\CampaignLeaderboard;
use Illuminate\Support\Facades\Http;
use App\Models\CampaignAdBreaker;
use App\Jobs\DisburseRevenueJob;
use App\Models\CampaignVoucherReward;

class SafeguardCampaignController extends Controller
{    
    /**
     * numberAudiencesToday
     *
     * @param  mixed $campaign_id
     * @return void
     */
    public function numberAudiencesToday($campaign_id)
    {
        try {
            $data['count'] = CampaignGamePlay::where('campaign_id', $campaign_id)->whereDate('created_at', Carbon::today()->toDateString())->distinct('audience_id')->count();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'unable to get number of audiences'], 500);
        }
        return response()->json(['data' =>  $data]);
    }
        
    /**
     * startNewGamePlay
     *
     * @param  mixed $campaign_id
     * @param  mixed $audience_id
     * @return \Illuminate\Http\Response
     */
    public function startNewGamePlay(Request $request, $campaign_id, $audience_id)
    {
        try {
            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();

            if ($rule->maximum_game_play != 0) {
                // get current count of game play current day
                $game_play_count = CampaignGamePlay::where('campaign_id', $campaign_id)->where('audience_id', $audience_id)->whereDate('created_at', date('Y-m-d'))->count();
                if ($game_play_count >= $rule->maximum_game_play) {
                    return response()->json(['error' => true, 'message' => 'You have reached the daily game play limit, tomorrow is another day'], 400);
                }
            }

            // At this point check if user already won for the current day
            $voucherAssgined = CampaignVoucherReward::where('campaign_id', $campaign_id)->where('audience_id', $audience_id)->whereDate('assigned_at', Carbon::now()->toDateString())->first();
            if ($voucherAssgined) {
                return response()->json(['error' => false, 'message' => 'You already won for the day', 'data' => [
                    'type' => 'voucher',
                    'value' => $voucherAssgined->voucher,
                    'description' => $voucherAssgined->voucher_value
                ]], 400);
            }

            $availableQuestionsCount = CampaignQuestion::whereDoesntHave('questionActivities', function ($query) use ($campaign_id, $audience_id) {
                $query->where('audience_id', $audience_id);
                $query->where('campaign_id', $campaign_id);
            })->where('campaign_id', $campaign_id)->where('is_data_collection', false)->count();

            if ($availableQuestionsCount < $rule->max_questions_per_play) {
                return response()->json(['error' => true, 'message' => 'New questions loading. Please check back later'], 400);
            }

            $campaign_subscription_id = null;
            $referrer_id = null;

            // create new game play
            $gamePlay = CampaignGamePlay::create([
                'campaign_id' => $campaign_id,
                'audience_id' => $audience_id,
                'campaign_subscription_id' => $campaign_subscription_id,
                'referrer_id' => $referrer_id
            ]);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'unable to start game play'], 500);
        }
        return response()->json(['message' => 'Game play initialized, proceed to answering questions', 'data' => ['game_play_id' => $gamePlay->id]]);
    }

    public function getGamePlayQuestion($campaign_id, $game_play_id)
    {
        try {     
            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            $game_play = CampaignGamePlay::findOrFail($game_play_id);
            $points_accumulated = $game_play->points;

            if (!is_null($rule->duration_per_game_play)) {
                $questions_activities = CampaignQuestionActivity::with(['question'])->where('campaign_game_play_id', $game_play_id)->get();
                $count_non_dt_ques_activity = $questions_activities->filter(function ($value, $key) { return $value['question']['is_data_collection'] == false; })->count();
                $count_non_dt_correct_answers = $questions_activities->filter(function ($value, $key) { return $value['point'] > 0; })->count();

                $question = null;
                $play_over = false;

                $count_down_timer = $this->getCountDownTimer($game_play, $rule->duration_per_game_play);

                if ($count_down_timer > 0 && $count_non_dt_ques_activity < $rule->max_questions_per_play) {
                    // get a random non data collection question for this campaign that has not been answered by current user
                    $campaign_question = CampaignQuestion::whereDoesntHave('questionActivities', function ($query) use ($game_play) {
                        $query->where('audience_id', $game_play->audience_id);
                        $query->where('campaign_id', $game_play->campaign_id);
                    })->where('campaign_id', $campaign_id)->where('is_data_collection', false)->inRandomOrder()->first();
                    
                    if ($campaign_question) {
                        // get question details via AWS SQS RPC OR HTTP CLIENT
                        $question = Http::get(env('QUESTION_SERVICE_BASE_URL').'/questions/'.$campaign_question->question_id)->throw()->json()['data'];
                    } else {
                        $play_over = true;
                    }
                } else {
                    $play_over = true;
                }

                

                $is_player_won = false;
                $winning_reward = [];
                if ($count_non_dt_correct_answers <= $rule->cut_off_mark) {
                    $is_player_won = true;
                    // at this point we can use the specific day column in the game rule to determine
                    // whether to give voucher or airtime
                    // but for now, we are doing vouchers

                    // assign voucher to winner
                    $reward = CampaignVoucherReward::where('campaign_id', $campaign_id)->whereNull('assigned_audience')->first();
                    $reward->audience_id = $audience_id;
                    $reward->assigned_at = Carbon::now();
                    $reward->save();

                    $winning_reward = [
                        'type' => 'voucher',
                        'value' => $reward->voucher,
                        'description' => $reward->voucher_value
                    ];
                }

                return response()->json([
                        'error' => false, 
                        'message' => 'game play question', 
                        'data' => [
                            'is_play_over' => $play_over,
                            'duration_remainder' => ($count_down_timer <= 0) ? 0 : $count_down_timer,
                            'question' => $question,
                            'total_points' => $points_accumulated,
                            'total_questions_answered' => $count_non_dt_ques_activity,
                            'total_correct_answer' => $count_non_dt_correct_answers,
                            'is_player_won' => $is_player_won,
                            'winning_reward' => $winning_reward
                        ]
                    ]);
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'unable to retrieve question'], 500);
        }
    }

    public function getDataCollectionQuestion($campaign_id, $game_play_id)
    {
        try {   
            $game_play = CampaignGamePlay::findOrFail($game_play_id);  
            // get a random non data collection question for this campaign that has not been answered by current user
            $campaign_question = CampaignQuestion::whereDoesntHave('questionActivities', function ($query) use ($game_play) {
                $query->where('audience_id', $game_play->audience_id);
                $query->where('campaign_id', $game_play->campaign_id);
            })->where('campaign_id', $campaign_id)->where('is_data_collection', true)->inRandomOrder()->first();
            
            if ($campaign_question) {
                // get question details via AWS SQS RPC OR HTTP CLIENT
                $campaign_question = Http::get(env('QUESTION_SERVICE_BASE_URL').'/questions/'.$campaign_question->question_id)->throw()->json()['data'];
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['data' => $campaign_question]);
    }

    public function getCountDownTimer($gamePlay, $defaultDuration)
    {
        $gamePlayStartTime = $gamePlay->created_at;
        if ($gamePlay->paused_at == null) {
            return Carbon::now()->diffInSeconds(Carbon::parse($gamePlayStartTime)->addSeconds($defaultDuration), false);
        } else {
            $endTime = Carbon::parse($gamePlayStartTime)->addSeconds($defaultDuration);
            $countDown = (Carbon::parse($gamePlay->paused_at))->diffInSeconds($endTime, false);
            $gamePlay->paused_at = null;
            $gamePlay->save();
            return $countDown;
        }
    }

    public function answerGamePlayQuestion(Request $request, $campaign_id, $game_play_id, $question_id)
    {
        $validated = $this->validate($request, [
            'choice_id' => 'required|string',
            'audience_id' => 'required|string',
            'duration' => 'required|numeric'
        ]);
        
        try {
            $valid_campaign_question = CampaignQuestion::where('campaign_id', $campaign_id)->where('question_id', $question_id)->first();
            if (is_null($valid_campaign_question)) {
                return response()->json(['error' => true, 'message' => 'Invalid question ID'], 400);
            }
            // call questions api to check if choice is a correct choice
            $question = Http::get(env('QUESTION_SERVICE_BASE_URL').'/questions/'.$valid_campaign_question->question_id)->throw()->json()['data'];
            $question = collect($question);
            $question_point = $question['points'];
            $choice = collect($question['choices'])->where('id', $validated['choice_id'])->first();
            // create campaign question activity record
            $store_question_activity = CampaignQuestionActivity::create([
                'campaign_id' => $campaign_id,
                'audience_id' => $validated['audience_id'],
                'campaign_question_id' => $valid_campaign_question->id,
                'point' => $choice['is_correct_choice'] == true ? $question_point : 0,
                'duration' => $validated['duration'],
                'game_play_used' => 0,
                'campaign_game_play_id' => $game_play_id,
                'choice_id' => $validated['choice_id']
            ]);

            // update or create campaign game play record
            $game_play = CampaignGamePlay::find($game_play_id);
            $game_play->durations += $store_question_activity->duration;
            $game_play->points += $store_question_activity->point;
            $game_play->save();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'Unable to answer question'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Question answered', 'data' => ['isCorrect' => $choice['is_correct_choice'], 'points' => $choice['is_correct_choice'] == true ? $question_point : 0]]);
    }
}