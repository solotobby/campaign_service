<?php

namespace App\Http\Controllers;

use http\Env\Response;
use Illuminate\Http\Request;
use App\Models\CampaignAdBreaker;
use App\Models\CampaignAdBreakerActivity;
use App\Models\CampaignGamePlay;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use App\Jobs\DisburseRevenueJob;
use Carbon\Carbon;

class CampaignAdBreakerController extends Controller
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
            $ad_breakers = CampaignAdBreaker::where('campaign_id', $campaign_id)->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Ad breakers', 'data' =>  $ad_breakers], 200);
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
            'ads' => 'required|array',
            'ads.*.on_question_num' => 'required|numeric',
            'ads.*.asset_url' => 'required|url',
            'ads.*.action_url' => 'nullable|url',
        ]);

        try {
            $ad_breakers = \DB::transaction(function () use ($validated, $campaign_id) {
                $temp = [];
                foreach ($validated['ads'] as $ad) {
                    $ad_breaker = CampaignAdBreaker::firstOrCreate([
                        'campaign_id' => $campaign_id,
                        'on_question_num' => $ad['on_question_num']
                    ], $ad);
                    array_push($temp, $ad_breaker);
                }
                return $temp;
            });
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Ad breaker', 'data' => $ad_breakers], 201);
    }

    /**
     * show
     *
     * @param  mixed $campaign_id
     * @param  mixed $ad_breaker_id
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id, $ad_breaker_id)
    {
        try {
            $ad_breaker = CampaignAdBreaker::find($ad_breaker_id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'somethign went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Ad breaker', 'data' =>  $ad_breaker], 200);
    }

    public function storeActivity(Request $request, $campaign_id, $ad_breaker_id)
    {
        $validated = $this->validate($request, [
            'audience_id' => 'required|uuid',
            'campaign_game_play_id' => 'required|uuid',
            'activity' => ['required','string', Rule::in(['VIEWED','ACTION_CLICKED'])]
        ]);

        try {
            $campaignGamePlay = CampaignGamePlay::findOrFail($validated['campaign_game_play_id']);
            $activity = CampaignAdBreakerActivity::create([
                    'campaign_id' => $campaign_id,
                    'campaign_ad_breaker_id' => $ad_breaker_id,
                    'audience_id' => $validated['audience_id'],
                    'campaign_game_play_id' => $validated['campaign_game_play_id'],
                    'activity' => $validated['activity']
            ]);
            // push ad revenue disburse onto queue
            if ($validated['activity'] == 'VIEWED') {
                $response = Http::post(env('WALLET_SERVICE_URL') .'/disburse/revenue', [
                    //'user_id' => $user->id
                    'channel' => 'arena',
                    'revenue_type' => 'ads',
                    'revenue' => '',// $rule->pay_as_you_go_amount,
                    'influencer_id' => $campaignGamePlay->referrer_id,
                    'audience_id' => $validated['audience_id'],
                    'campaign_id' => $campaign_id,
                    'activity_id' => $activity->id, //to be changed to game play ID
                ]);
                if ($response->serverError() || $response->clientError()) {
                    return response()->json([
                        'message' => $response->json(),
                    ], 400);
                }
               //$job = (new DisburseRevenueJob($campaignGamePlay->referrer_id, $validated['audience_id'], $campaign_id, $activity->id, 'arena', 'ads'));
//                $this->dispatch($job);
            }
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Ad breaker activity registered', 'data' => $activity], 201);
    }

    public function saveAdsTime(Request $request)
    {
        $validated = $this->validate($request, [
            'time' => 'required',
            'game_play_id' => 'required|uuid'
        ]);

        try{
            $get = CampaignGamePlay::where('id', $validated['game_play_id'])->first();
            if($get == null)
            {
                return response()->json(['error' => true, 'message' => 'invalid Game play Id']);
            }
            $get->time = $validated['time'];
            $get->save();
        }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()]);
        }

        return response()->json(['error' => false, 'message'=> 'time added to game play']);

    }

    public function getAdsTime(Request $request)
    {
        $validated = $this->validate($request, [
            'game_play_id' => 'required|uuid'
        ]);

        try{
            $get = CampaignGamePlay::where('id', $validated['game_play_id'])->first();
            if($get == null)
            {
                return response()->json(['error' => true, 'message' => 'invalid Game play Id']);
            }
            $data['time'] = $get->time;
            $data['game_play_id'] = $get->id;
         }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()]);
        }
        return response()->json(['error' => false, 'data'=> $data]);
    }
}
