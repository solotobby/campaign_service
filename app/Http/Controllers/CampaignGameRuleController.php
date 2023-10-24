<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignGameRule;
use Illuminate\Support\Str;
use App\Models\CampaignSubscriptionPlan;

class CampaignGameRuleController extends Controller
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
            $rules = CampaignGameRule::where('campaign_id', $campaign_id)->first();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign game rules', 'data' =>  $rules], 200);
    }
    
    /**
     * create
     *
     * @param  mixed $request
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'leaderboard_num_winners' => 'nullable|numeric|gt:0',
            'cut_off_mark' => 'nullable|numeric',
            'maximum_game_play' => 'required|numeric|gt:0',
            'maximum_win' => 'required|numeric|gt:0',
            'is_data_collection' => 'required|boolean',
            'is_subscription_based' => 'required|boolean',
            'has_free_game_play' => 'required|boolean',
            'num_free_game_plays' => 'required_if:has_free_game_play,true|numeric',
            'has_referral' => 'required|boolean',
            'referral_points' => 'required_if:has_referral,true|numeric',
            'has_ad_breaker' => 'required|boolean',
            'has_leaderboard' => 'required|boolean',
            'duration_per_game_play' => 'nullable|integer',
            'interval_data_collection' => 'required_if:is_data_collection,true|numeric|gt:0||different:interval_display_ad',
            'interval_display_ad' => 'required_if:has_ad_breaker,true|numeric|gt:0|different:interval_data_collection',
            'max_questions_per_play' => 'required|integer|gte:0',
            'is_pay_as_you_go' => 'required|boolean',
            'pay_as_you_go_amount' => 'required_if:is_pay_as_you_go,true|numeric|gt:0',
            'payout' => 'nullable|string|in:'.implode(',', ['wallet', 'bank']),
            'import_opentdb_questions' => 'required|boolean'
        ]);

        try {
            $rules = CampaignGameRule::updateOrCreate(['campaign_id' => $campaign_id], $validated);
            if ($validated['has_free_game_play'] == true) {
                // create a free subscription plan
                CampaignSubscriptionPlan::updateOrCreate([
                    'campaign_id' => $campaign_id,
                    'price' => 0.0,
                    'title' => 'freemium'
                ], [
                    'game_plays' => $validated['num_free_game_plays']
                ]);
            }
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'campaign game rules created', 'data' => $rules], 201);
    }
    
    /**
     * show
     *
     * @param  mixed $campaign_id
     * @param  mixed $subscription_id
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id, $rule_id)
    {
        try {
            $rules = CampaignGameRule::find($rule_id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'somethign went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign game rules', 'data' =>  $rules], 200);
    }
}
