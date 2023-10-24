<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignSubscriptionPlan;
use Illuminate\Support\Str;

class CampaignSubscriptionPlanController extends Controller
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
            $plans = CampaignSubscriptionPlan::where('campaign_id', $campaign_id)->where('price', '>', 0.0)->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign subscription plans', 'data' =>  $plans], 200);
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
            'plans' => 'required|array',
            'plans.*.title' => 'nullable|string',
            'plans.*.price' => 'required|numeric|gte:0',
            'plans.*.game_plays' => 'required|integer'
        ]);
        
        try {
            $plans = \DB::transaction(function () use ($validated, $campaign_id) {
                $temp = [];
                foreach ($validated['plans'] as $plan) {
                    $plan = CampaignSubscriptionPlan::firstOrCreate([
                        'campaign_id' => $campaign_id,
                        'price' => $plan['price'],
                        'game_plays' => $plan['game_plays']
                    ], ['title' => $plan['title']]);
                    array_push($temp, $plan);
                }
                return $temp;
            });
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'campaign subscription plans created', 'data' => $plans], 201);
    }
    
    /**
     * show
     *
     * @param  mixed $campaign_id
     * @param  mixed $subscription_id
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id, $subscription_id)
    {
        try {
            $plan = CampaignSubscriptionPlan::find($subscription_id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'somethign went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign subscription plan details', 'data' =>  $plan], 200);
    }
}
