<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Models\CampaignMobileReward;
use App\Models\CampaignMobileRedemption;

class CampaignMobileRewardController extends Controller
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
            $rewards = CampaignMobileReward::where('campaign_id', $campaign_id)->get();
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign Mobile Rewards', 'data' =>  $rewards], 200);
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
            'rewards.*.type' => 'required|string|in:'.implode(',', ['AIRTIME', 'DATA', 'CASH']),
            'rewards.*.reward' => 'required|numeric',
            'rewards.*.quantity' => 'required|integer',
            'rewards.*.icon_url' => 'sometimes|required|string',
            'rewards.*.specific_days' => 'sometimes|required|json',
            'rewards.*.cash_reward_to_wallet' => 'required_if:rewards.*.type,CASH|boolean',
            'rewards.*.cash_reward_to_bank' => 'required_if:rewards.*.cash_reward_to_wallet,false|boolean'
        ]);

        try {
            $rewards = \DB::transaction(function () use ($validated, $campaign_id) {
                $temp = [];
                foreach ($validated['rewards'] as $reward) {
                    $reward["quantity_remainder"] = $reward["quantity"];
                    $mobile_reward = CampaignMobileReward::firstOrCreate([
                        'campaign_id' => $campaign_id,
                        'type' => $reward['type'],
                        'reward' => $reward['reward'],
                        'quantity' => $reward['quantity']
                    ], $reward);
                    array_push($temp, $mobile_reward);
                }
                return $temp;
            });
        } catch (\Throwable $th) {
            //Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign rewards', 'data' => $rewards], 201);
    }

    /**
     * show
     *
     * @param  mixed $campaign_id
     * @param  mixed $reward_id
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id, $reward_id)
    {
        try {
            $reward = CampaignMobileReward::find($reward_id);
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
            $campaign = Campaign::findOrFail($campaign_id);
            $reward = CampaignMobileReward::findOrFail($reward_id);
            $redemption_activity = CampaignMobileRedemption::create([
                'campaign_id' => $campaign_id,
                'campaign_mobile_reward_id' => $reward_id,
                'audience_id' => $audience_id,
                'status' => $validated['status']
            ]);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Instant mobile redemption was successful', 'data' =>  $redemption_activity], 200);
    }

    public function airtimeReward(Request $request)
    {
        $validated = $this->validate($request, [
            'phone' => 'required|numeric'
        ]);

        try{

        }catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Instant mobile redemption was successful'], 200);
    }
}
