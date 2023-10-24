<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignGamePlayPurchase;
use App\Models\CampaignQuestionActivity;
use App\Models\Campaign;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CampaignController extends Controller
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

    public function activeCampaigns()
    {
        try {
            $campaigns = Campaign::where('status', 'ACTIVE')->orderBy('created_at', 'desc')->get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return $campaigns;
    }

    public function fetchCampaigns($title)
    {
        try{
            $fetchedCampaign = Campaign::where('title', $title)->first()->id;
        }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'data' => $fetchedCampaign], 200);
    }

    /**
     * index
     *
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // get the company where user_id belongs and query campaigns table
            // we could also check the permissions and access level of the user before returning campaigns
            $campaigns = Campaign::get();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaigns', 'data' =>  $campaigns], 200);
    }

    /**
     * storeInformation
     *
     * @param  mixed $request
     * @return \Illuminate\Http\Response
     */
    public function storeInformation(Request $request)
    {
        $validated = $this->validate($request, [
            'type' => 'required|string|min:3',
            'title' => 'required|string|min:3',
            'client_id' => 'required|uuid',
            'brand_id' => 'required|uuid',
            'company_id' => 'required|uuid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'daily_start' => 'sometimes|required|date_format:H:i:s',
            'daily_stop' => 'sometimes|required|date_format:H:i:s'
        ]);

        try {
            $campaign = Campaign::firstOrCreate([
                'title' => $validated['title'],
                'client_id' => $validated['client_id'],
                'brand_id' => $validated['brand_id'],
                'company_id' => $validated['company_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date']
            ], $validated);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaigns', 'data' =>  $campaign], 201);
    }

    /**
     * storeForecast
     *
     * @param  mixed $request
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function storeForecast(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'daily_ads_budget' => 'required',
            'total_ads_budget' => 'required',
        ]);
    }

    /**
     * show
     *
     * @param  mixed $var
     * @return \Illuminate\Http\Response
     */
    public function show($campaign_id)
    {
        try {
            $campaign = Campaign::find($campaign_id);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign information', 'data' =>  $campaign], 200);
    }
}
