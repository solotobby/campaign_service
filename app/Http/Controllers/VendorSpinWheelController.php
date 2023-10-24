<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignVendorSpinWheel;
use App\Models\CampaignVendorSpinWheelReward;
use Illuminate\Http\Request;


class VendorSpinWheelController extends Controller
{

    public function index()
    {
        try{
            $data = CampaignVendorSpinWheel::all();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'cannot fetch resource'], 500);
        }
        return response()->json(['error' => false, 'message' => 'SpinWheel Vendor List', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|numeric|digits:11'
        ]);

        try{
            $data = CampaignVendorSpinWheel::create(['name' => $validated['name'], 'email' => $validated['email'], 'phone' => $validated['phone']]);
        } catch (\Throwable $th){
            report($th);
            return response()->json(['error' => true, 'message' => 'cannot create vendor'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Vendor Created', 'data' => $data]);
    }

    public function play(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'audience_id' => 'required|uuid'
        ]);
        try{

            $campaign = Campaign::where('id', $campaign_id)->first();
            if($campaign == null)
            {
                throw new \Exception("Campaign not found", 500);
            }
            $vendor = CampaignVendorSpinWheel::where('id', $campaign->vendor_id)->first();
            if($vendor == null)
            {
                throw new \Exception("Vendor not valid", 500);
            }
            $double_dollar_sign = "Cash Prize";
            $question_mark = "Start Again";
            $phone_sign = "Airtime";
            $social_media_icons = "Data Bundle";
            $blanks = "Nothing";

            $wheels = array($double_dollar_sign, $question_mark,$phone_sign,$social_media_icons,$blanks);
            $spinResult = $wheels[array_rand($wheels, 1)];

            $reward = '';
            $redirect = '';
            if($spinResult == "Cash Prize")
            {
               $reward = CampaignVendorSpinWheelReward::create([
                   'vendor_id' => $vendor->id,
                   'audience_id' => $validated['audience_id'],
                   'type' => 'CASH',
                   'value' => '200',
                   'is_redeem' => false,
               ]);
            }

            if($spinResult == "Start Again")
            {
                $reward = "Free Game Play";
                $redirect = 'redirect-to-free-game-play';
            }

            if($spinResult == "Nothing")
            {
                $reward = "No prize, spin wheel again";
                $redirect = null;

            }

            if($spinResult == "Airtime")
            {
                $reward = CampaignVendorSpinWheelReward::create([
                    'vendor_id' => $vendor->id,
                    'audience_id' => $validated['audience_id'],
                    'type' => 'AIRTIME',
                    'value' => '100',
                    'is_redeem' => false,
                ]);
            }

            if($spinResult == "Data Bundle")
            {
                $reward = CampaignVendorSpinWheelReward::create([
                    'vendor_id' => $vendor->id,
                    'audience_id' => $validated['audience_id'],
                    'type' => 'DATA',
                    'value' => '100',
                    'is_redeem' => false,
                ]);
            }

            $data['prize'] = $spinResult;
            $data['reward'] = $reward;
            $data['vendor'] = $vendor;
            $data['redirect'] = $redirect;
        } catch (\Throwable $th){
            report($th);
            return response()->json(['error' => true, 'message' => 'something happen, cannot play game'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Play successful', 'data' => $data], 200);
    }

    public function redeemVendorReward($campaign_id, $audience_id)
    {
        try{
            $campaign = Campaign::where('id', $campaign_id)->first();
            if($campaign == null)
            {
                throw new \Exception("Campaign not found", 500);
            }
            $vendor = CampaignVendorSpinWheel::where('id', $campaign->vendor_id)->first();
            if($vendor == null)
            {
                throw new \Exception("Vendor not valid", 500);
            }
            $reward = CampaignVendorSpinWheelReward::where('vendor_id', $vendor->id)->where('audience_id', $audience_id)->get();
            $data['vendor'] = $vendor;
            $data['redeem'] = $reward;
        }catch (\Throwable $th){
            report($th);
            return response()->json(['error' => true, 'message' => 'something happen, cannot play game'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Vendor List', 'data' => $data]);
    }



}
