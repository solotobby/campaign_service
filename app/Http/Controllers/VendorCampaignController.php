<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignVendorSpinWheel;
use App\Models\CampaignVendorSpinWheelReward;
use Illuminate\Http\Request;


class VendorCampaignController extends Controller
{

    public function index($vendor_id)
    {
        try {
            $vendor = CampaignVendorSpinWheel::where('id', $vendor_id)->first();
            $campaigns = Campaign::where('vendor_id', $vendor_id)->get();
            $data['vendor'] = $vendor;
            $data['campaign_count'] = $campaigns->count();
            $data['campaigns'] = $campaigns;
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Vendor Campaigns', 'data' =>  $data], 200);
    }

    public function storeCampaign(Request $request, $vendor_id)
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
                'end_date' => $validated['end_date'],
                'vendor_id' => $vendor_id
            ], $validated);
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Vendor Campaign Created', 'data' =>  $campaign], 201);
    }
}
