<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignLeaderboardRedemption;
use App\Http\Resources\RedemptionResource;
use Carbon\Carbon;

class CampaignRewardRedemptionController extends Controller
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
    public function index(Request $request, $campaign_id, $audience_id)
    {
        $validated = $this->validate($request, [
            'range' => 'nullable|integer'
        ]);

        try {
            $rewards = CampaignLeaderboardRedemption::with(['campaignLeaderboardReward'])->where('campaign_id', $campaign_id)
                        ->where('audience_id', $audience_id)
                        ->when(!is_null($validated['range']), function ($query) use ($validated) {
                            $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                        })->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return RedemptionResource::collection($rewards);
    }

    public function todayWinning($campaign_id, $audience_id)
    {
        try {
            // comment so ci/cd can run
            $reward = CampaignLeaderboardRedemption::with(['campaignLeaderboardReward'])->where('campaign_id', $campaign_id)
                        ->where('audience_id', $audience_id)
                        ->whereDate('created_at', Carbon::today())
                        ->first();
            if (is_null($reward)) {
                return response()->json(["message" => "no winnings was found"], 200);
            }
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'message' => 'unable to today winning'], 500);
        }
        return new RedemptionResource($reward);
    }
}
