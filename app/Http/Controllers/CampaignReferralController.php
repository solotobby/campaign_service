<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignReferral;
use Illuminate\Support\Str;

class CampaignReferralController extends Controller
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

    public function index($campaign_id)
    {
        $referrals = CampaignReferral::where('campaign_id', $campaign_id)->get();
        return response()->json(['error' => false, 'message' => 'List referrals for campaign', 'data' => $referrals], 200);
    }

    public function create(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'referrer_id' => 'required|string',
        ]);

        try {
            $referral = CampaignReferral::firstOrCreate([
                'campaign_id' => $campaign_id,
                'referrer_id' => $validated['referrer_id'],
            ],[
                'code' => Str::uuid()
            ]);
        } catch (\Throwable $th) {
            //Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Referral code generated', 'data' => $referral], 201);
    }

    public function show($campaign_id, $id)
    {
        try {
            $referral = CampaignReferral::findOrFail($id);
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign referral code retrieved by ID', 'data' => $referral], 200);
    }

    public function showByReferrer($campaign_id, $referrer_id)
    {
        try {
            $referral = CampaignReferral::where('campaign_id', $campaign_id)->where('referrer_id', $referrer_id)->firstOrFail();
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign referral code retrieved by ID', 'data' => $referral], 200);
    }

    public function referralCountForLeaderboard(Request $request, $campaign_id)
    {
        try {
            $referrals = CampaignReferral::whereHas('referents', function ($query) {
                $query->where('is_activated', true);
                // $query->where('is_activation_point_redeemed', false);
                $query->whereDate('updated_at', date('Y-m-d'));
            })->withCount(['referents' => function ($query) {
                $query->where('is_activated', true);
                // $query->where('is_activation_point_redeemed', false);
                $query->whereDate('updated_at', date('Y-m-d'));
            }])->where('campaign_id', $campaign_id)->get()->pluck('referents_count', 'referrer_id')->all();

        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['data' => $referrals], 200);
    }
}
