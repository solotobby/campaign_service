<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignGame;
use App\Models\CampaignGamePlay;
use App\Models\CampaignGameRule;
use App\Models\Campaign;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\CampaignLeaderboard;
use Illuminate\Support\Facades\Http;
use App\Jobs\DisburseRevenueJob;

class CampaignGameController extends Controller
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
            $games = CampaignGame::where('campaign_id', $campaign_id)->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Games', 'data' =>  $games], 200);
    }

    /**
     * create
     *
     * @param  mixed $request
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'games' => 'required|array',
            'games.*.id' => 'required|uuid'
        ]);

        try {
            $campaign_games = \DB::transaction(function () use ($validated, $campaign_id) {
                $temp = [];
                foreach ($validated['games'] as $game) {
                    $campaign_game = CampaignGame::firstOrCreate([
                        'campaign_id' => $campaign_id,
                        'game_id' => $game['id']
                    ], $game);
                    array_push($temp, $campaign_game);
                }
                return $temp;
            });
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Games', 'data' => $campaign_games], 201);
    }

    /**
     * show
     *
     * @param  mixed $campaign_id
     * @param  mixed $game_id
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id, $game_id)
    {
        try {
            $game = CampaignGame::find($game_id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'somethign went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Game', 'data' =>  $game], 200);
    }

    /**
     * startNewGamePlay
     *
     * @param  mixed $campaign_id
     * @param  mixed $audience_id
     * @return \Illuminate\Http\Response
     */
    public function startNewGamePlay(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'influencer_username' => 'sometimes|required|string',
            'campaign_game_id' => 'required|uuid',
            'audience_id' => 'required|uuid'
        ]);

        try {
            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();

            if ($rule->maximum_game_play != 0) {
                // get current count of game play current day
                $game_play_count = CampaignGamePlay::where('campaign_id', $campaign_id)->where('audience_id', $validated['audience_id'])->whereDate('created_at', date('Y-m-d'))->count();
                if ($game_play_count >= $rule->maximum_game_play) {
                    return response()->json(['error' => true, 'message' => 'You have reached the daily game play limit, tomorrow is another day'], 400);
                }
            }

            $campaign_subscription_id = null;
            $referrer_id = null;

            if ($request->has('influencer_username') && !is_null($request->influencer_username)) {
                $referrer = Http::get(env('AUDIENCE_URL').'/v1/audience/convert-username-id?user_name='.$request->influencer_username);
                if ($referrer->successful()) {
                    $referrer_id = $referrer->json()['id'];
                }
            }

            $eligible_free_game_play = false;
            // if campaign has free game play?
            if ($rule->has_free_game_play && $rule->num_free_game_plays > 0) {
                $game_play_count = CampaignGamePlay::where('campaign_game_id', $validated['campaign_game_id'])->where('audience_id', $validated['audience_id'])->count();
                if ($game_play_count < $rule->num_free_game_plays) {
                    $eligible_free_game_play = true;
                }
            }

            if ($rule->is_pay_as_you_go) {
                $payload = [
                    'user_id' => $validated['audience_id'],
                    'amount' => $rule->pay_as_you_go_amount,
                    'platform' => 'arena', //update this later by getting platform name from .env
                    'trans_type' => 'purchase-game-play',
                    'reference' => $campaign_id
                ];
                $wallet_response = Http::post(env('WALLET_SERVICE_URL').'/debit', $payload);
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
                'audience_id' => $validated['audience_id'],
                'campaign_subscription_id' => $campaign_subscription_id,
                'campaign_game_id' => $validated['campaign_game_id'],
                'referrer_id' => $referrer_id
            ]);

            if ($rule->is_pay_as_you_go && $eligible_free_game_play == false) {
                $response = Http::post(env('WALLET_SERVICE_URL') .'/disburse/revenue', [
                    //'user_id' => $user->id
                    'channel' => 'arena',
                    'revenue_type' => 'subscription',
                    'revenue' => $rule->pay_as_you_go_amount,
                    'influencer_id' => $referrer_id,
                    'audience_id' => $validated['audience_id'],
                    'campaign_id' => $campaign_id,
                    'activity_id' => $gamePlay->id, //to be changed to game play ID
                ]);
                if ($response->serverError() || $response->clientError()) {
                    return response()->json([
                        'message' => $response->json(),
                    ], 400);
                }
//                $job = (new DisburseRevenueJob($referrer_id, $validated['audience_id'], $campaign_id, $gamePlay->id, 'arena', 'subscription', $rule->pay_as_you_go_amount));
//                $this->dispatch($job);
            }

        }catch (\Exception $exception){
            //report($exception);
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'Game play initialized, proceed to playing game', 'data' => ['game_play_id' => $gamePlay->id]]);
    }

    public function registerGameActivity(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'points' => 'required|numeric',
            'durations' => 'required|numeric',
            'audience_id' => 'required|string',
            'game_play_id' => 'required|string'
        ]);

        try {
            // update or create campaign game play record
            $game_play = CampaignGamePlay::find($validated['game_play_id']);
            $game_play->durations += $validated['durations'];
            $game_play->points += $validated['points'];
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
                $leaderboard = CampaignLeaderboard::create([
                    'campaign_id' => $campaign_id,
                    'audience_id' => $validated['audience_id'],
                    'play_points' => $validated['points'],
                    'play_durations' => $validated['durations'],
                    'total_points' => $validated['points']
                ]);
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'game activity cannot be registered'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Game play activity registered']);
    }
}
