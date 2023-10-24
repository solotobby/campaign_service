<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignLeaderboardReward;
use App\Models\CampaignLeaderboardRedemption;

class CampaignLeaderboardRewardController extends Controller
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
            $rewards = CampaignLeaderboardReward::where('campaign_id', $campaign_id)->get();
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Leaderboard Rewards', 'data' =>  $rewards], 200);
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
            'rewards' => 'required|array',
            'rewards.*.type' => 'required|string|in:'.implode(',', ['AIRTIME', 'DATA', 'VOUCHER', 'CASH']),
            'rewards.*.player_position' => 'required|numeric|gte:0',
            'rewards.*.reward' => 'required|string',
            'rewards.*.description' => 'sometimes|required|string',
            'rewards.*.frequency' => 'required|string|in:'.implode(',', ['DAILY', 'WEEKLY', 'MONTHLY', 'ALL-TIME']),
            'rewards.*.icon_url' => 'sometimes|required|string',
            'rewards.*.specific_days' => 'sometimes|required|json',
            'rewards.*.cash_reward_to_wallet' => 'required_if:rewards.*.type,CASH|boolean',
            'rewards.*.cash_reward_to_bank' => 'required_if:rewards.*.cash_reward_to_wallet,false|boolean',
            'rewards.*.voucher_redemption_mode' => 'required_if:rewards.*.type,VOUCHER|string|in:'.implode(',', ['ONLINE', 'OFFLINE']),
            'rewards.*.voucher_redemption_expiry' => 'required_if:rewards.*.type,VOUCHER|numeric',
            'rewards.*.voucher_redemption_url' => 'required_if:rewards.*.voucher_redemption_mode,ONLINE|url',
            'rewards.*.top_players_start' => 'required_if:rewards.*.player_position,0|numeric|gt:0',
            'rewards.*.top_players_end' => 'required_with:rewards.*.top_players_start|numeric|gt:rewards.*.voucher_redemption_url',
            'rewards.*.top_players_revenue_share_percent' => 'required_if:rewards.*.top_players,gt,0|numeric|gt:0',
        ]);

        try {
            $rewards = \DB::transaction(function () use ($validated, $campaign_id) {
                $temp = [];
                foreach ($validated['rewards'] as $reward) {
                    $leaderboard_reward = CampaignLeaderboardReward::firstOrCreate([
                        'campaign_id' => $campaign_id,
                        'type' => $reward['type'],
                        'frequency' => $reward['frequency']
                    ], $reward);
                    array_push($temp, $leaderboard_reward);
                }
                return $temp;
            });
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign rewards', 'data' => $rewards], 201);
    }

    /**
     * show
     *
     * @param  mixed $campaign_id
     * @param  mixed $game_id
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id, $reward_id)
    {
        try {
            $reward = CampaignLeaderboardReward::find($reward_id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'somethign went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Reward', 'data' =>  $reward], 200);
    }

    public function storeRedemption(Request $request, $campaign_id, $reward_id, $audience_id)
    {
        $validated = $this->validate($request, [
            'status' => 'required|string'
        ]);

        try {
//            $campaign = Campaign::findOrFail($campaign_id);
//            $reward = CampaignLeaderboardReward::findOrFail($reward_id);
            $redemption_activity = CampaignLeaderboardRedemption::create([
                'campaign_id' => $campaign_id,
                'campaign_leaderboard_reward_id' => $reward_id,
                'audience_id' => $audience_id,
                'status' => $validated['status']
            ]);
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'mesage' => 'somethign went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Leaderboard redemption was successful', 'data' =>  $redemption_activity], 200);
    }
}
