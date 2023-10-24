<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArielInfluencer;
use App\Models\ArielInfluencerActivity;
use App\Models\ArielShoppingSite;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ArielCampaignController extends Controller
{    
    /**
     * getShoppingSites
     *
     * @return void
     */
    public function getShoppingSites()
    {
        try {
            $sites = ArielShoppingSite::get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'unable to get ariel shopping sites'], 500);
        }
        return response()->json(['data' =>  $sites], 200);
    }
    
    /**
     * redeemReferralCode
     *
     * @param  mixed $code
     * @return void
     */
    public function redeemReferralCode($code)
    {
        try {
            $code = Str::replace('%20', ' ', $code);
            $influencer = ArielInfluencer::where('code', strtolower($code))->firstOrFail();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'Invalid referral code'], 400);
        }
        return response()->json($influencer);
    }
    
    /**
     * registerInfluecingActivites
     *
     * @param  mixed $request
     * @return void
     */
    public function registerInfluencingActivites(Request $request)
    {
        $validated = $this->validate($request, [
            'influencer_id' => 'required|exists:ariel_influencers,id',
            'shopping_site_id' => 'required|exists:ariel_shopping_sites,id'
        ]);

        try {
            $activy = ArielInfluencerActivity::create([
                'ariel_influencer_id' => $validated['influencer_id'],
                'ariel_shopping_site_id' => $validated['shopping_site_id'],
            ]);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'Unable to register influencing activity'], 500);
        }
        return response()->json(['message' => 'Influcencing activity registered']);
    }

    public function analytics(Request $request)
    {
        $validated = $this->validate($request, [
            'range' => 'nullable|integer'
        ]);

        try {
            $data = [];
            $data['shopping_sites'] = ArielShoppingSite::withCount(['influencerActivities' => function ($query) use ($validated) {
                                            $query->when(!is_null($validated['range']), function ($query) use ($validated) {
                                                $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                                            });
                                        }])->get();
            $data['influencers'] = ArielInfluencer::withCount(['influencerActivities' => function ($query) use ($validated) {
                                            $query->when(!is_null($validated['range']), function ($query) use ($validated) {
                                                $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                                            });
                                        }])->get();
            $data['unique_referral_codes_inputted'] = ArielInfluencerActivity::when(!is_null($validated['range']), function ($query) use ($validated) {
                                                            $query->whereDate('created_at', '>=', Carbon::today()->subDays($validated['range']));
                                                        })->count();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(["message" => "unable to fetch campaign analytics"], 500);
        }
        return response()->json(["data" => $data]);
    }
}
