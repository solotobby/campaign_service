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

class CampaignGamePlayController extends Controller
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
            $plays = CampaignGamePlay::where('campaign_id', $campaign_id)->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign subscription plays', 'data' =>  $plays], 200);
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
        $this->validate($request, [
            'referrer_id' => 'sometimes|required|string',
            'influencer_username' => 'sometimes|required|string',
        ]);

        try {

                $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();

                if ($rule->maximum_game_play != 0) {
                    // get current count of game play current day
                    $game_play_count = CampaignGamePlay::where('campaign_id', $campaign_id)->where('audience_id', $audience_id)->whereDate('created_at', date('Y-m-d'))->count();
                    if ($game_play_count >= $rule->maximum_game_play) {
                        return response()->json(['error' => true, 'message' => 'You have reached the daily game play limit, tomorrow is another day'], 400);
                    }
                }

                $availableQuestionsCount = CampaignQuestion::whereDoesntHave('questionActivities', function ($query) use ($campaign_id, $audience_id) {
                    $query->where('audience_id', $audience_id);
                    $query->where('campaign_id', $campaign_id);
                })->where('campaign_id', $campaign_id)->where('is_data_collection', false)->count();
//                $availableQuestionsCount = 800;
                if ($availableQuestionsCount < $rule->max_questions_per_play) {
                    return response()->json(['error' => true, 'message' => 'New questions loading. Please check back later'], 400);
                }

                $campaign_subscription_id = null;
                $referrer_id = null;

                if ($request->has('influencer_username') && !is_null($request->influencer_username)) {
                    $referrer = Http::get(env('AUDIENCE_URL') . '/v1/audience/convert-username-id?user_name=' . $request->influencer_username);
                    if ($referrer->successful()) {
                        $referrer_id = $referrer->json()['id'];
                    }
                } elseif ($request->has('referrer_id')) {
                    $referrer_id = $request->referrer_id;
                }

                if ($rule->is_subscription_based) {
                    // TODO - check if the last gamePlay has no question activities
                    // check if audience has valid subscription for this campaign
                    $subscription = CampaignSubscription::where('campaign_id', $campaign_id)->where('audience_id', $audience_id)->latest()->first();
                    if (is_null($subscription) || $subscription->available_game_plays <= 0) {
                        return response()->json(['error' => true, 'message' => 'No active subscription or game play'], 400);
                    }
                    // decrease available_game_plays by 1
                    $subscription->available_game_plays -= 1;
                    $subscription->save();
                    $campaign_subscription_id = $subscription->id;
                } elseif ($rule->is_pay_as_you_go) {
                    // UNCOMMENT THE CODE BELOW TO GET PAY AS YOU GO WORKING
                    //make a call to wallet service to charge the audience
                    $payload = [
                        'user_id' => $audience_id,
                        'amount' => $rule->pay_as_you_go_amount,
                        'platform' => 'arena', //update this later by getting platform name from .env
                        'trans_type' => 'purchase-game-play',
                        'reference' => $campaign_id
                    ];
                    $wallet_response = Http::post(env('WALLET_SERVICE_URL') . '/debit', $payload);
                    if ($wallet_response->serverError() || $wallet_response->clientError()) {
                        return response()->json([
                            'message' => $wallet_response->json(),
                            'redirect_to_wallet_page' => true
                        ], 400);
                    }
                }

                // create new game play
                $gamePlay = CampaignGamePlay::create([
                    'campaign_id' => $campaign_id,
                    'audience_id' => $audience_id,
                    'campaign_subscription_id' => $campaign_subscription_id,
                    'referrer_id' => $referrer_id
                ]);



                if ($rule->is_pay_as_you_go) {

                    $response = Http::post(env('WALLET_SERVICE_URL') .'/disburse/revenue', [
                        //'user_id' => $user->id
                        'channel' => 'arena',
                        'revenue_type' => 'subscription',
                        'revenue' => $rule->pay_as_you_go_amount,
                        'influencer_id' => $referrer_id,
                        'audience_id' => $audience_id,
                        'campaign_id' => $campaign_id,
                        'activity_id' => $gamePlay->id, //to be changed to game play ID
                    ]);
                    if ($response->serverError() || $response->clientError()) {
                        return response()->json([
                            'message' => $response->json(),
                        ], 400);
                    }
//
//                   $data['disbursement'] = json_decode($response->getBody()->getContents(), true);;
//                   $data['game_play_id'] = $gamePlay->id;//json_decode($response->getBody()->getContents(), true);;
//                $job = (new DisburseRevenueJob($referrer_id, $audience_id, $campaign_id, $gamePlay->id, 'arena', 'subscription', $rule->pay_as_you_go_amount));
//                $this->dispatch($job);

                }
        } catch (\Exception $exception) {

            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
//        return response()->json(['error' => false, 'message' => 'Game play initialized, proceed to answering questions', 'data' => $data]);
        return response()->json(['error' => false, 'message' => 'Game play initialized, proceed to answering questions', 'data' => ['game_play_id' => $gamePlay->id]]);
    }

    public function v1getGamePlayQuestionAd($campaign_id, $game_play_id)
    {
        try {
            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            $game_play = CampaignGamePlay::findOrFail($game_play_id);

            $points_accumulated = $game_play->points;
            $count_non_dt_questions_answered = 0;

            if (!is_null($rule->duration_per_game_play)) {
                // get datetime the first question was answered
                $non_dt_question_answered = CampaignQuestionActivity::where('campaign_game_play_id', $game_play_id)->whereHas('question', function ($query) {
                    $query->where('is_data_collection', false);
                })->get();

                $count_non_dt_questions_answered = $non_dt_question_answered->count();
                $question = null;
                $ad = null;
                $play_over = false;
                $display_ads = false;
                $display_data_collection = false;
                $data_collection_question = null;


                // check to display ads, if there's no ad, display data collection question
                $countAdBreaker = CampaignAdBreakerActivity::where('campaign_game_play_id', $game_play_id)->count();
                $countDtQuestionsAnswered = CampaignQuestionActivity::where('campaign_game_play_id', $game_play_id)->whereHas('question', function ($query) {
                                                $query->where('is_data_collection', true);
                                            })->count();
                $sumAdBreakerWithDTQAnswered = $countAdBreaker + $countDtQuestionsAnswered;

                $remainingDuration = $this->computeRemainingDuration($non_dt_question_answered, $rule, $sumAdBreakerWithDTQAnswered);

                if ($rule->has_ad_breaker && $rule->interval_display_ad > 0
                    && $count_non_dt_questions_answered > 0
                    && ($count_non_dt_questions_answered % $rule->interval_display_ad) == 0
                    && ($count_non_dt_questions_answered / $rule->interval_display_ad) > $sumAdBreakerWithDTQAnswered) {
                    # fetch campaign ads that player has not seen in this game play
                    $ad = CampaignAdBreaker::whereDoesntHave('activities', function ($query) use ($game_play) {
                        $query->where('audience_id', $game_play->audience_id);
                        $query->where('campaign_id', $game_play->campaign_id);
                        $query->where('campaign_game_play_id', $game_play->id);
                    })->where('campaign_id', $campaign_id)->inRandomOrder()->first();

                    if (!$ad) {
                        # fetch campaign data collection question that player has not seen in this game play
                        $dt_question = CampaignQuestion::whereDoesntHave('questionActivities', function ($query) use ($game_play) {
                            $query->where('audience_id', $game_play->audience_id);
                            $query->where('campaign_id', $game_play->campaign_id);
                        })->where('campaign_id', $campaign_id)->where('is_data_collection', true)->inRandomOrder()->first();

                        if ($dt_question) {
                            // get question details via AWS SQS RPC OR HTTP CLIENT
                            $data_collection_question = Http::get(env('QUESTION_SERVICE_BASE_URL').'/questions/'.$dt_question->question_id)->throw()->json()['data'];
                            $display_data_collection = true;
                        }
                    } else {
                        $display_ads = true;
                    }
                }
                // no data collection question and no ad
                elseif ($count_non_dt_questions_answered <= 15 && ($count_non_dt_questions_answered == 0 || $remainingDuration > 0)) {
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
                    $display_ads = false;
                    $play_over = true;
                }

                return response()->json([
                        'error' => false,
                        'message' => 'game play question',
                        'data' => [
                            'is_play_over' => $play_over,
                            'display_ads' => $display_ads,
                            'ads' => $ad,
                            'display_data_collection' => $display_data_collection,
                            'data_collection_question' => $data_collection_question,
                            'duration_remainder' => $this->computeRemainingDuration($non_dt_question_answered, $rule, $sumAdBreakerWithDTQAnswered),
                            'question' => $question,
                            'total_points' => $points_accumulated,
                            'total_questions_answered' => $count_non_dt_questions_answered
                        ]
                    ]);
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
    }

    public function getGamePlayQuestionAd($campaign_id, $game_play_id)
    {
        try {
            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            $game_play = CampaignGamePlay::findOrFail($game_play_id);

            $points_accumulated = $game_play->points;

            if (!is_null($rule->duration_per_game_play)) {
                $questions_activities = CampaignQuestionActivity::with(['question'])->where('campaign_game_play_id', $game_play_id)->get();
                $count_non_dt_ques_activity = $questions_activities->filter(function ($value, $key) { return $value['question']['is_data_collection'] == false; })->count();
                $count_dt_ques_activity = $questions_activities->filter(function ($value, $key) { return $value['question']['is_data_collection'] == true; })->count();
                $count_ad_breaker = CampaignAdBreakerActivity::where('campaign_game_play_id', $game_play_id)->count();
                $sumAdBreaker = $count_dt_ques_activity + $count_ad_breaker;

                $question = null;
                $ad = null;
                $play_over = false;
                $display_ads = false;
                $display_data_collection = false;
                $data_collection_question = null;

                $is_display_ads = $this->isDisplayAds($count_non_dt_ques_activity, $sumAdBreaker, $rule->has_ad_breaker, $rule->interval_display_ad);
                $count_down_timer = $this->getCountDownTimer($game_play, $rule->duration_per_game_play, $is_display_ads);

                if ($count_down_timer > 0 && $is_display_ads) {
                    # fetch campaign ads that player has not seen in this game play
                    $ad = CampaignAdBreaker::whereDoesntHave('activities', function ($query) use ($game_play) {
                        $query->where('audience_id', $game_play->audience_id);
                        $query->where('campaign_id', $game_play->campaign_id);
                        $query->where('campaign_game_play_id', $game_play->id);
                    })->where('campaign_id', $campaign_id)->inRandomOrder()->first();

                    if (!$ad) {
                        # fetch campaign data collection question that player has not seen in this game play
                        $dt_question = CampaignQuestion::whereDoesntHave('questionActivities', function ($query) use ($game_play) {
                            $query->where('audience_id', $game_play->audience_id);
                            $query->where('campaign_id', $game_play->campaign_id);
                        })->where('campaign_id', $campaign_id)->where('is_data_collection', true)->inRandomOrder()->first();

                        if ($dt_question) {
                            // get question details via AWS SQS RPC OR HTTP CLIENT
                            $data_collection_question = Http::get(env('QUESTION_SERVICE_BASE_URL').'/questions/'.$dt_question->question_id)->throw()->json()['data'];
                            $display_data_collection = true;
                        }
                    } else {
                        $display_ads = true;
                    }
                } elseif ($count_down_timer > 0 && $count_non_dt_ques_activity < $rule->max_questions_per_play) {
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

                return response()->json([
                        'error' => false,
                        'message' => 'game play question',
                        'data' => [
                            'is_play_over' => $play_over,
                            'display_ads' => $display_ads,
                            'ads' => $ad,
                            'display_data_collection' => $display_data_collection,
                            'data_collection_question' => $data_collection_question,
                            'duration_remainder' => ($count_down_timer <= 0) ? 0 : $count_down_timer,
                            'question' => $question,
                            'total_points' => $points_accumulated,
                            'total_questions_answered' => $count_non_dt_ques_activity
                        ]
                    ]);
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
    }

    public function isDisplayAds($countNonDtQuestionsAcitivities, $sumAdBreaker, $campaignHasAds, $adDisplayInterval)
    {
        // \Log::info("sumAdBreaker: ".$sumAdBreaker);
        // \Log::info("countNoDataQuestions: ".$countNonDtQuestionsAcitivities);
        // \Log::info("division: ".$countNonDtQuestionsAcitivities / $adDisplayInterval);

        if ($campaignHasAds == false || $countNonDtQuestionsAcitivities == 0 || ($countNonDtQuestionsAcitivities % $adDisplayInterval) != 0) {
            return false;
        } elseif (($countNonDtQuestionsAcitivities % $adDisplayInterval) == 0 && ($countNonDtQuestionsAcitivities / $adDisplayInterval) > $sumAdBreaker) {
            return true;
        }
        return false;
    }

    public function getCountDownTimer($gamePlay, $defaultDuration, $isDisplayAds)
    {
        if ($isDisplayAds) {
            $gamePlay->paused_at = Carbon::now();
            $gamePlay->save();
            return $defaultDuration - (Carbon::parse($gamePlay->created_at)->diffInSeconds(Carbon::parse($gamePlay->paused_at)));
        }
        else {
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
    }

    public function computeRemainingDuration($nonDtQuestionsAnswered, $rule, $sumAdBreakerWithDTQAnswered)
    {
        # check count_non_data_collection_question_activities equal 0 for ads or data collection question
        # if yes carbon::now() should be replace will the created_at of the last non DT question answered
        $countNonDTQuestions = $nonDtQuestionsAnswered->count();
        if ($rule->has_ad_breaker && $rule->interval_display_ad > 0
                    && $countNonDTQuestions > 0
                    && ($countNonDTQuestions % $rule->interval_display_ad) == 0
                    && ($countNonDTQuestions / $rule->interval_display_ad) > $sumAdBreakerWithDTQAnswered) {
            $result = $rule->duration_per_game_play - (Carbon::parse($nonDtQuestionsAnswered->last()->created_at))->diffInSeconds(Carbon::parse($nonDtQuestionsAnswered->first()->created_at));
            return ($result < 0) ? 0 : $result;
        }
        elseif ($countNonDTQuestions == 0) {
            return $rule->duration_per_game_play;
        }
        else {
            $now = ($countNonDTQuestions > 1) ? Carbon::parse($nonDtQuestionsAnswered->last()->created_at) : Carbon::now();
            $result = $rule->duration_per_game_play - ($now)->diffInSeconds(Carbon::parse($nonDtQuestionsAnswered->first()->created_at));
            return ($result < 0) ? 0 : $result;
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

            //  get game play with the highest points and durations for the current day
            $todayBestGamePlay = CampaignGamePlay::where('audience_id', $validated['audience_id'])
                                                ->where('campaign_id', $campaign_id)
                                                ->whereDate('created_at', Carbon::now()->toDateString())
                                                ->orderBy('points', 'desc')
                                                ->first();
            // update leaderboard record
            $leaderboard = CampaignLeaderboard::where('campaign_id', $campaign_id)
                                ->where('audience_id', $validated['audience_id'])
                                ->whereDate('created_at', Carbon::now()->toDateString())->first();
            if ($leaderboard && $todayBestGamePlay) {
                if ($leaderboard->play_points < $todayBestGamePlay->points) {
                    $leaderboard->play_durations = $todayBestGamePlay->durations;
                    $leaderboard->play_points = $todayBestGamePlay->points;
                    $leaderboard->total_points = $todayBestGamePlay->points;
                    $leaderboard->save();
                }
            } else {
                if ($store_question_activity->point > 0) {
                    $leaderboard = CampaignLeaderboard::create([
                        'campaign_id' => $campaign_id,
                        'audience_id' => $validated['audience_id'],
                        'play_points' => $store_question_activity->point,
                        'play_durations' => $store_question_activity->duration,
                        'total_points' => $store_question_activity->point
                    ]);
                }
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Question answered', 'data' => ['isCorrect' => $choice['is_correct_choice'], 'points' => $choice['is_correct_choice'] == true ? $question_point : 0]]);
    }

    public function gamePlaySummary($campaign_id, $game_play_id)
    {
        try {
            $game_play = CampaignGamePlay::findOrFail($game_play_id);
            $questions_activities = CampaignQuestionActivity::where('campaign_game_play_id', $game_play_id)->get();
            if (is_null($questions_activities)) {
                return response()->json(['error' => true, 'mesage' => 'no questions was answered for this game play'], 400);
            }
            $leaderboard = CampaignLeaderboard::where('campaign_id', $campaign_id)
                                ->whereDate('created_at', Carbon::now()->toDateString())
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->get();

            $player_position = 0;

            if (count($leaderboard) > 0) {
                $player_record = $leaderboard->where('audience_id', $game_play->audience_id)->first();
                if ($player_record) {
                    $player_position = (int) array_search($game_play->audience_id, array_column($leaderboard->toArray(), 'audience_id')) + 1;
                }
            }

            $response = [
                'total_answered' => $questions_activities->count(),
                'total_correct_answer' => $questions_activities->where('point', '!=', 0)->count(),
                'player_leaderboard_position' => $player_position,
                'current_game_play' => 1,
                'total_points' => $questions_activities->sum('point'),
                'duration' => number_format((($questions_activities->sum('duration')) / 1000), 2)
            ];
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign audience stat for last game play', 'data' =>  $response]);
    }

    public function influencingSummary(Request $request)
    {
        $validated = $this->validate($request, [
            'start' => 'nullable|date',
            'end' => 'nullable|date',
            'audience_id' => 'required|string',
            'range' => 'nullable|integer'
        ]);

        try {
            $data = [];
            $data['count'] = CampaignGamePlay::where('referrer_id', $validated['audience_id'])
                                    ->when(!is_null($validated['range']), function ($query) use ($validated) {
                                        $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                                    })->count();
        } catch (\Exception $exception) {
            //report($th);
            return response()->json(["error"=>true,  "message" => $exception->getMessage()], 500);
        }
        return response()->json(["data" => $data]);
    }

    public function totalNumberOfGamePlay(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'audience_id' => 'required|uuid'
        ]);

        try{
            $gameplayCount = CampaignGamePlay::where('audience_id', $validated['audience_id'])->count();
        }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'total_game_played' => $gameplayCount], 200);
    }
}
