<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\CampaignReferralActivity;
use App\Models\CampaignLeaderboard;
use App\Models\CampaignSubscriptionPlan;
use App\Models\CampaignSubscription;
use Carbon\Carbon;
use App\Models\Campaign;
class IsabiSportController extends Controller
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
     * create a new top up transaction for a given user
     *
     * @param  mixed $request
     * @return void
     */
    public function verifyPayment(Request $request)
    {
        $validated = $this->validate($request, [
            'third_party_ref' => 'required|string',
            'transaction_id' => 'required|string',
            'subscription_id' => 'required|string',
            'audience_id' => 'required|string'
        ]);

        try {
            // verify third party reference
            $build_url = env('ISABI_SPORT_BASE_URL')."init-charge/".$validated['third_party_ref']."/".$validated['transaction_id'];
            $verifiedPayment = Http::withHeaders(['Authorization' => env('ISABI_SPORT_AUTHORIZATION_KEY')])->get($build_url)->throw()->json();
            if ($verifiedPayment['statusCode'] != 200) {
                return response()->json(['error' => true, 'message' => 'Payment status is '.$verifiedPayment['message']], 422);
            }
            $amount = ($verifiedPayment['response']['amount']);
            // get subscription by id
            $subscriptionPlan = CampaignSubscriptionPlan::find($validated['subscription_id']);
            if ($subscriptionPlan['price'] != $amount) {
                return response()->json(['error' => true, 'message' => 'Subscription price does not match the amount you paid'], 422);
            }
            // check if payment reference has been used
            $subscriptionWithPaymentRef = CampaignSubscription::where('payment_reference', $validated['third_party_ref'])->where('campaign_id', $subscriptionPlan->campaign_id)->first();
            if ($subscriptionWithPaymentRef) {
                return response()->json(['error' => true, 'message' => 'Payment reference has been used already'], 422);
            }
            // get or create audience subscription
            $new_subscription = CampaignSubscription::create([
                'campaign_id' => $subscriptionPlan->campaign_id,
                'audience_id' => $validated['audience_id'],
                'campaign_subscription_plan_id' => $subscriptionPlan->id,
                'payment_reference' => $validated['third_party_ref'],
                'allocated_game_plays' => $subscriptionPlan->game_plays,
                'available_game_plays' => $subscriptionPlan->game_plays
            ]);

            // check if referral has been activated, if no, activate referral
            $unactivated_referral = CampaignReferralActivity::with(['Referral'])
                                                ->where('referent_id', $validated['audience_id'])
                                                ->where('campaign_id', $subscriptionPlan['campaign_id'])
                                                ->where('is_activated', false)
                                                ->first();

            if ($unactivated_referral) {
                // check if its past daily stop. if yes then add the point to the next date
                $campaignDailyStopReached = Campaign::whereDate('start_date', '<=', (Carbon::now())->toDateString())
                            ->whereDate('end_date', '>=', (Carbon::now())->toDateString())
                            ->whereNotNull('daily_stop')
                            ->whereTime('daily_stop', '<=', (Carbon::now())->toTimeString())
                            ->where('id', $subscriptionPlan['campaign_id'])
                            ->first();

                // campaignDailyStopReached is true then create or update leaderboard record for tomorrow
                if ($campaignDailyStopReached) {
                    $tomorrowLeaderboard = CampaignLeaderboard::where('campaign_id', $subscriptionPlan['campaign_id'])
                                                ->where('audience_id', $unactivated_referral->Referral->referrer_id)
                                                ->whereDate('created_at', (Carbon::tomorrow())->toDateString())
                                                ->first();
                    if ($tomorrowLeaderboard) {
                        $tomorrowLeaderboard->referral_points += 2;
                        $tomorrowLeaderboard->total_points += $tomorrowLeaderboard->referral_points;
                        $tomorrowLeaderboard->play_durations += 0;
                        $tomorrowLeaderboard->save();
                    } else {
                        $tomorrowLeaderboard = new CampaignLeaderboard();
                        $tomorrowLeaderboard->campaign_id = $subscriptionPlan['campaign_id'];
                        $tomorrowLeaderboard->audience_id = $unactivated_referral->Referral->referrer_id;
                        $tomorrowLeaderboard->referral_points = 2;
                        $tomorrowLeaderboard->total_points = 2;
                        $tomorrowLeaderboard->play_durations += 0;
                        $tomorrowLeaderboard->created_at = Carbon::tomorrow();
                        $tomorrowLeaderboard->updated_at = Carbon::tomorrow();
                        $tomorrowLeaderboard->save();
                    }
                } else {
                    // campaignDailyStopReached is false create or update record for current day
                    // Also add to campaign leaderboard referral point
                    $todayLeaderboard = CampaignLeaderboard::where('campaign_id', $subscriptionPlan['campaign_id'])
                                                ->where('audience_id', $unactivated_referral->Referral->referrer_id)
                                                ->whereDate('created_at', date('Y-m-d'))
                                                ->first();
                    if ($todayLeaderboard) {
                        $todayLeaderboard->referral_points += 2;
                        $todayLeaderboard->total_points += $todayLeaderboard->referral_points;
                        $todayLeaderboard->play_durations += 0;
                        $todayLeaderboard->save();
                    } else {
                        CampaignLeaderboard::create([
                            'campaign_id' => $subscriptionPlan['campaign_id'],
                            'audience_id' => $unactivated_referral->Referral->referrer_id,
                            'play_durations' => 0,
                            'referral_points' => 2,
                            'total_points' => 2
                        ]);
                    }
                }
                // update referral activity
                $unactivated_referral->is_activated = true;
                $unactivated_referral->is_activation_point_redeemed = true;
                $unactivated_referral->save();
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign subscription was successful', 'data' => $new_subscription]);
    }
    
    /**
     * Generate authorization url where user can make payment, reference is also generated
     *
     * @param  mixed $request
     * @return Response
     */
    public function initializeCharge(Request $request)
    {
        $validated = $this->validate($request, [
            'email' => 'required|email',
            'subscription_id' => 'required|string',
            'audience_id' => 'required|string',
            'redirect_url' => 'required|url'
        ]);

        try {
            // get subscription by id
            $subscription = CampaignSubscriptionPlan::find($validated['subscription_id']);
            if ($subscription == null) {
                return response()->json(['error' => true, 'message' => 'Invalid subscription id'], 422);
            }
            $build_url = env('ISABI_SPORT_BASE_URL')."init-charge?email=".$validated['email']."&amount=".$subscription['price']."&url=".$validated['redirect_url']."?sub_id=".$validated['subscription_id'];
            $response = Http::withHeaders(['Authorization' => env('ISABI_SPORT_AUTHORIZATION_KEY')])->post($build_url)->throw()->json();
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'payment initialized', 'data' => $response]);
    }
}
