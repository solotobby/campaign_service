<?php

namespace App\Http\Controllers;

use App\Models\CampaignGamePrize;
use App\Models\CampaignMobileReward;
use App\Models\CampaignSpinWheelReward;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Models\CampaignGame;
use App\Models\CampaignGamePlay;
use App\Models\CampaignGameRule;
use App\Models\Campaign;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\CampaignLeaderboard;
use Illuminate\Support\Facades\Http;
use App\Jobs\DisburseRevenueJob;

class SpinWheelGamePlayController extends Controller
{

    public function initializeGamePlay($campaign_id)
    {
        try{
            $campaign = Campaign::where('id', $campaign_id)->first();
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'data' => $campaign]);
    }
    public function play(Request $request, $campaign_id)
    {

        $validated = $this->validate($request, [
            'influencer_username' => 'sometimes|required|string',
            'audience_id' => 'required|uuid'
        ]);

        try{

            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();

            if ($rule->maximum_game_play != 0) {
                // get current count of game play current day
                $game_play_count = CampaignGamePlay::where('campaign_id', $campaign_id)->where('audience_id', $validated['audience_id'])->whereDate('created_at', date('Y-m-d'))->count();
                if ($game_play_count >= $rule->maximum_game_play) {
                    return response()->json(['error' => true, 'message' => 'You have reached the daily game play limit, tomorrow is another day'], 400);
                }
            }

            ///check if audience has enough balance,
            /// if yes, charge else throw error
            $payload = [
                'user_id' => $validated['audience_id'],
                'amount' => $rule->pay_as_you_go_amount,
                'platform' => 'arena', //update this later by getting platform name from .env
                'trans_type' => 'purchase-game-play',
                'reference' => $campaign_id
            ];
           // dd($payload);
            $wallet_response = Http::post(env('WALLET_SERVICE_URL').'/debit', $payload);
            //dd($wallet_response);
            if ($wallet_response->serverError() || $wallet_response->clientError()) {
                return response()->json([
                    'message' => $wallet_response->json(),
                    'redirect_to_wallet_page' => true
                ], 400);
            }


            $double_dollar_sign = "Cash Prize";
            $question_mark = "Start Again";
            $phone_sign = "Airtime";
            $social_media_icons = "Data Bundle";
            $blanks = "Nothing";

            $wheels = array($double_dollar_sign, $question_mark,$phone_sign,$social_media_icons,$blanks);
            $spinResult = $wheels[array_rand($wheels, 1)];
            if($spinResult == "Cash Prize")
            {
                $cashPrize = CampaignGamePrize::where('name', 'CASH')->first();
                $load = [
                    'user_id' => $validated['audience_id'],
                    'amount' => $cashPrize->amount,
                    'platform' => 'arena',
                    'trans_type' => 'game-play-reward-credit',
                    'reference' => $campaign_id
                ];
                $credit_wallet = Http::post(env('WALLET_SERVICE_URL').'/credit', $load);
                if ($credit_wallet->serverError() || $credit_wallet->clientError()) {
                    return response()->json([
                        'message' => $credit_wallet->json(),
                        'redirect_to_wallet_page' => true
                    ], 400);
                }else{
                    $message = "Cash Price";
                    $data = CampaignMobileReward::create([
                        'campaign_id' => $campaign_id,
                        'type' => 'CASH',
                        'reward' => $cashPrize->amount,
                        'quantity' => '1',
                        'quantity_remainder' => '1',
                        'cash_reward_to_bank' => false,
                        'cash_reward_to_wallet' => true
                    ]);
                }
            }

            if($spinResult == "Start Again")
            {
                $message = "Start Again";
                $data = 'redirect-to-free-game-play';
            }

            if($spinResult == "Airtime")
            {
                $airtime = CampaignGamePrize::where('name', 'AIRTIME')->first();
                    $message = "Airtime";
                     CampaignMobileReward::create([
                        'campaign_id' => $campaign_id,
                        'type' => 'AIRTIME',
                        'reward' => $airtime->amount,
                        'quantity' => '1',
                        'quantity_remainder' => '1',
                        'cash_reward_to_wallet' => true,
                        'cash_reward_to_bank' => false
                    ]);

                $data = CampaignSpinWheelReward::create([
                        'campaign_id' => $campaign_id,
                        'audience_id' => $validated['audience_id'],
                        'type' => 'AIRTIME',
                        'value' => $airtime->amount,
                        'status' => null
                    ]);
            }

            if($spinResult == "Data Bundle")
            {
                $dataBundle = CampaignGamePrize::where('name', 'DATA')->first();
                    $message = "Data Bundle";
                    CampaignMobileReward::create([
                        'campaign_id' => $campaign_id,
                        'type' => 'DATA',
                        'reward' => $dataBundle->amount,
                        'quantity' => '1',
                        'quantity_remainder' => '1',
                        'cash_reward_to_wallet' => true,
                        'cash_reward_to_bank' => false
                    ]);
                    $data = CampaignSpinWheelReward::create([
                        'campaign_id' => $campaign_id,
                        'audience_id' => $validated['audience_id'],
                        'type' => 'DATA',
                        'value' => $dataBundle->amount,
                        'status' => null
                    ]);
            }

            if($spinResult == "Nothing")
            {
                $message = "Nothing";
                $data = 'No prize, spin wheel again';
            }

            $referrer_id = null;
            $campaign_subscription_id = null;
            //register campaign in Gamplay
            $gamePlay = CampaignGamePlay::create([
                'campaign_id' => $campaign_id,
                'audience_id' => $validated['audience_id'],
                'campaign_subscription_id' => $campaign_subscription_id,
                'referrer_id' => $referrer_id
            ]);

            //only disburse if reward is data bundle, airtime or cash
            if($spinResult == "Data Bundle" || $spinResult == "Airtime" || $spinResult == "Cash Prize"){
                $response = Http::post(env('WALLET_SERVICE_URL') .'/disburse/revenue', [
                    //'user_id' => $user->id
                    'channel' => 'arena',
                    'revenue_type' => 'subscription',
                    'revenue' => $rule->pay_as_you_go_amount,
                    'influencer_id' => $referrer_id,
                    'audience_id' => $validated['audience_id'],
                    'campaign_id' => $campaign_id,
                    'activity_id' => $gamePlay->id, //to be changed to game play ID
                ]);
                if ($response->serverError() || $response->clientError()) {
                    return response()->json([
                        'message' => $response->json(),
                    ], 400);
                }
            }



        } catch (\Throwable $th) {
//            report($th);
            return response()->json(['error' => true, 'message' => 'cannot start game play'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Spin Wheel Play Successful', 'prize' => $message,  'data' => $data]);
//        return response()->json(['error' => false, 'message' => 'Game play initialized, proceed to playing game', 'data' => ['game_play_id' => $gamePlay->id]]);

    }

    public function playFree(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'audience_id' => 'required|uuid'
        ]);


        try {
            $rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();

            if ($rule->maximum_game_play != 0) {
                // get current count of game play current day
                $game_play_count = CampaignGamePlay::where('campaign_id', $campaign_id)->where('audience_id', $validated['audience_id'])->whereDate('created_at', date('Y-m-d'))->count();

                if ($game_play_count >= $rule->maximum_game_play) {
                    return response()->json(['error' => true, 'message' => 'You have reached the daily game play limit, tomorrow is another day'], 400);
                }
            }

            $double_dollar_sign = "Cash Prize";
            $question_mark = "Start Again";
            $phone_sign = "Airtime";
            $social_media_icons = "Data Bundle";
            $blanks = "Nothing";

            $wheels = array($double_dollar_sign, $question_mark,$phone_sign,$social_media_icons,$blanks);
            $spinResult = $wheels[array_rand($wheels, 1)];

            if($spinResult == "Cash Prize")
            {
                $cashPrize = CampaignGamePrize::where('name', 'CASH')->first();

                $load = [
                    'user_id' => $validated['audience_id'],
                    'amount' => $cashPrize->amount,
                    'platform' => 'arena',
                    'trans_type' => 'game-play-reward-credit',
                    'reference' => $campaign_id
                ];
                $credit_wallet = Http::post(env('WALLET_SERVICE_URL').'/credit', $load);
                if ($credit_wallet->serverError() || $credit_wallet->clientError()) {
                    return response()->json([
                        'message' => $credit_wallet->json(),
                        'redirect_to_wallet_page' => true
                    ], 400);
                }else{

                    $message = "Cash Price";
                    $data = CampaignMobileReward::create([
                        'campaign_id' => $campaign_id,
                        'type' => 'CASH',
                        'reward' => $cashPrize->amount,
                        'quantity' => '1',
                        'quantity_remainder' => '1',
                        'cash_reward_to_bank' => true,
                        'cash_reward_to_wallet' => false
                    ]);
                }
            }

            if($spinResult == "Start Again")
            {
                $message = "Start Again";
                $data = 'redirect-to-free-game-play';
            }

            if($spinResult == "Airtime")
            {
                $airtime = CampaignGamePrize::where('name', 'AIRTIME')->first();
                $message = "Airtime";
                $data = CampaignMobileReward::create([
                    'campaign_id' => $campaign_id,
                    'type' => 'AIRTIME',
                    'reward' => $airtime->amount,
                    'quantity' => '1',
                    'quantity_remainder' => '1',
                    'cash_reward_to_wallet' => true,
                    'cash_reward_to_bank' => false
                ]);

                CampaignSpinWheelReward::create([
                    'campaign_id' => $campaign_id,
                    'audience_id' => $validated['audience_id'],
                    'type' => 'AIRTIME',
                    'value' => $airtime->amount,
                    'status' => null
                ]);
            }

            if($spinResult == "Data Bundle")
            {
                $dataBundle = CampaignGamePrize::where('name', 'DATA')->first();
                $message = "Data Bundle";
                $data = CampaignMobileReward::create([
                    'campaign_id' => $campaign_id,
                    'type' => 'DATA',
                    'reward' => $dataBundle->amount,
                    'quantity' => '1',
                    'quantity_remainder' => '1',
                    'cash_reward_to_wallet' => true,
                    'cash_reward_to_bank' => false
                ]);

                CampaignSpinWheelReward::create([
                    'campaign_id' => $campaign_id,
                    'audience_id' => $validated['audience_id'],
                    'type' => 'DATA',
                    'value' => $dataBundle->amount,
                    'status' => null
                ]);
            }

            if($spinResult == "Nothing")
            {
                $message = "Nothing";
                $data = 'No prize, spin wheel again';
            }
        } catch (\Throwable $th) {
//            report($th);
            return response()->json(['error' => true, 'message' => 'cannot start game play'], 500);
        }
        return response()->json(['error' => false, 'prize' => $message, 'message' => 'Spin Wheel Play Successful', 'data' => $data]);
    }




}
