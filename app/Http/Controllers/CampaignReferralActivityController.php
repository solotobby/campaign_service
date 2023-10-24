<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignReferral;
use App\Models\CampaignReferralActivity;
use Illuminate\Support\Str;

class CampaignReferralActivityController extends Controller
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

    public function index($campaign_id, $referral_id)
    {
        $referents = CampaignReferralActivity::where('campaign_referral_id', $referral_id)->get();
        return response()->json(['error' => false, 'message' => 'List referents for a given referral id/code', 'data' => $referents], 200);
    }

    public function create(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'referent_id' => 'required|string',
            'referral_code' => 'required|string'
        ]);
        
        try {
            // get referral by code
            $referral = CampaignReferral::where('campaign_id', $campaign_id)->where('code', $validated['referral_code'])->first();
            if ($referral == null) {
                return response()->json(['error' => true, 'message' => 'Invalid referral code'], 422);
            }
            // check for duplicate
            $referent = CampaignReferralActivity::firstOrCreate([
                'campaign_id' => $campaign_id,
                'referent_id' => $validated['referent_id']
            ], ['campaign_referral_id' => $referral->id]);
        } catch (\Throwable $th) {
            Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Referent registered', 'data' => $referent], 201);
    }

    public function show($campaign_id, $id)
    {
        try {
            $referral = CampaignReferralActivity::findOrFail($id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Referral activity retrieved by ID', 'data' => $referral], 200);
    }
    
    /**
     * activate Referent
     * set is_activated to true. This way the referral will be ready for redemption by the referrer
     * @param  mixed $campaign_id
     * @param  mixed $referent_id
     * @return Response
     */
    public function activateReferent($campaign_id, $referent_id)
    {
        try {
            $referent = CampaignReferralActivity::where('campaign_id', $campaign_id)->where('referent_id', $referent_id)->first();
            if ($referent == null) {
                return response()->json(['error' => true, 'message' => 'No referral was found'], 422);
            }
            $referent->is_activated = true;
            $referent->save();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign referral has been activated', 'data' => $referent], 200);
    }
    
    /**
     * updateRedeemActivationPoint
     * get all referent referred by a user, check if their referral was activated and point
     * has not been redeemed for those activation
     * @param  mixed $campaign_id
     * @param  mixed $referrer_id
     * @return Response
     */
    public function updateRedeemActivationPoint($campaign_id, $referrer_id)
    {
        try {
            // get referents by referrer_id and campaign_id
            $referents = CampaignReferralActivity::whereHas('Referral', function ($query) use ($referrer_id, $campaign_id) {
                $query->where('referrer_id', $referrer_id)->where('campaign_id', $campaign_id);
            })->where('is_activated', true)->where('is_activation_point_redeemed', false)->get();

            if ($referents == null) {
                return response()->json(['error' => false, 'message' => 'Number of active and unredeem referrals', 'data' => ['num_of_active_unredeemed_referent' => 0]], 200);
            }
            \DB::transaction(function () use ($referents){
                foreach ($referents as $referent) {
                    CampaignReferralActivity::where('id', $referent->id)->update(['is_activation_point_redeemed' => 1]);
                }
            });
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Number of active and unredeem referrals', 'data' => ['num_of_active_unredeemed_referent' => count($referents)]], 200);
    }
}
