<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignVendorSpinWheel;
use App\Models\CampaignVendorSpinWheelReward;
use App\Services\AirtimeService;
use Illuminate\Http\Request;
use App\Models\CampaignGameRule;
use Illuminate\Support\Str;
use App\Models\CampaignSubscriptionPlan;
use AfricasTalking\SDK\AfricasTalking;

class VendorSpinWheelRewardController extends Controller
{
     public function airtimeReward(Request $request, $campaign_id)
     {
         $validated = $this->validate($request, [
             'audience_id' => 'required|uuid',
             'phone_number' => 'required|numeric|digits:11'
         ]);
         try {

             $campaign = Campaign::where('id', $campaign_id)->first();
             if ($campaign == null) {
                 throw new \Exception("Campaign not found", 500);
             }

             $vendor = CampaignVendorSpinWheel::where('id', $campaign->vendor_id)->first();
             if($vendor == null)
             {
                 throw new \Exception("Vendor not valid", 500);
             }

             $reward = CampaignVendorSpinWheelReward::where('vendor_id', $vendor->id)->where('audience_id', $validated['audience_id'])
                 ->where('type', 'AIRTIME')->where('is_redeem', false)->get();
             //$data['vendor'] = $vendor;
             $amount = $reward->sum('value');

             $phone = '+234'.substr($validated['phone_number'], 1);

             $airtime = $this->rechargeAirtime($phone, $amount);
             //dd($airtime);

             $data = json_decode($airtime);

         }catch (\Throwable $th){
             report($th);
             return response()->json(['error' => true, 'message' => 'something happen, try again'], 500);
         }
         return response()->json(['error' => false, 'message' => 'Redeemable Value', 'data' => $data], 200);
     }

     public function rechargeAirtime($phone, $amount)
     {
         // Set your app credentials
         $username = "sandbox";
         $apiKey = "8cdd129d84a3c6eb68790d532c8699a18a7a53dc7212173225a9e4c3a81ba9d4";

        // Initialize the SDK
         $AT = new AfricasTalking($username, $apiKey);

        // Get the airtime service
         $airtime  = $AT->airtime();

        // Set the phone number, currency code and amount in the format below
         $recipients = [[
             "phoneNumber"  => $phone,
             "currencyCode" => "NGN",
             "amount"       => $amount
         ]];

         try {
             // That's it, hit send and we'll take care of the rest
             $results = $airtime->send([
                 "recipients" => $recipients
             ]);
             $response = json_encode($results, true);
            return $response;
         } catch(Exception $e) {
             echo "Error: ".$e->getMessage();
         }
     }

}
