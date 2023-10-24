<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignGamePlayPurchase;
use App\Models\CampaignGameRule;
use App\Models\CampaignSubscriptionPlan;
use App\Models\CampaignSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CampaignGamePlayPurchaseController extends Controller
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
     * List purchases by audience_id
     *
     * @return Response
     */
    public function index($audience_id)
    {
        $subscriptions = CampaignGamePlayPurchase::where('audience_id', $audience_id)->get();
        return response()->json(['error' => false, 'message' => 'List subscriptions for all campaigns', 'data' => $subscriptions], 200);
    }

    /**
     * Show campaign purchase by audience_id
     *
     * @param  mixed $campaign_id
     * @param  mixed $audience_id
     * @return void
     */
    public function getLatestSubscription($campaign_id, $audience_id)
    {
        try {
            $subscription = CampaignSubscription::where('campaign_id', $campaign_id)->where('audience_id', $audience_id)->latest()->first();
            if (is_null($subscription)) {
                return response()->json(['error' => true, 'message' => 'No active subscription or game play'], 400);
            }
        } catch (\Exception $exception) {
           // report($th);
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign game play purchase retrieved by audience ID', 'data' => $subscription], 200);
    }

    /**
     * Retrieve campaign game play purchase for the given ID.
     *
     * @param  mixed $id
     * @return Response
     */
    public function show($id)
    {
        try {
            $purchase = CampaignGamePlayPurchase::findOrFail($id);
        } catch (\Exception $exception) {
            // report($th);
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign game play purchase retrieved by ID', 'data' => $purchase], 200);
    }

    /**
     * create a new game play purchase for a given user
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $validated = $this->validate($request, [
            'audience_id' => 'required|string',
            'campaign_id' => 'required|string',
            'num_plays' => 'required|numeric|gt:0',
        ]);
        try {
            // charge audience wallet - 1 play is equivalent to 50 naira
            $amount  = $validated['num_plays'] * 50;
            $buy_play_url = env('WALLET_BASE_URL').'/wallets/transactions/purchase-game-play';
            $response = Http::post($buy_play_url, ["user_id" => $validated['audience_id'],"amount" => $amount]);
            if ($response->clientError() || $response->serverError()) {
                return response()->json($response->json(), $response->status());
            }
            // get or create audience subscription
            $purchase = CampaignGamePlayPurchase::firstOrCreate([
                'audience_id' => $validated['audience_id'],
                'campaign_id' => $validated['campaign_id'],
            ]);
            $purchase->total_purchased += $validated['num_plays'];
            $purchase->total_remaining += $validated['num_plays'];
            $purchase->save();
        } catch (\Exception $exception) {
            // report($th);
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'Purchase created', 'data' => $purchase], 201);
    }

    /**
     * consumeGamePlay
     *
     * @param  mixed $campaign_id
     * @param  mixed $audience_id
     * @return void
     */
    public function consumeGamePlay($campaign_id, $audience_id)
    {
        try {
            $subscription = CampaignGamePlayPurchase::where('audience_id', $audience_id)->where('campaign_id', $campaign_id)->first();
            if ($subscription != null) {
                if ($subscription->total_remaining > 0) {
                    $subscription->total_remaining -= 1;
                    $subscription->total_consumed += 1;
                    $subscription->save();
                }
            }
        } catch (\Exception $exception) {
            // report($th);
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign game play purchase retrieved by audience ID', 'data' => $subscription], 200);
    }

    public function freeGamePlays(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'campaign_id' => 'required|string',
            'audience_id' => 'required|string'
        ]);

        try {
            // get game rules and check if campaign has free game play
            $game_rule = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            if ($game_rule['has_free_game_play'] == false) {
                return response()->json(['error' => false, 'message' => 'campaign does not have free game play', 'data' => []], 400);
            }
            // check subscription plans for a free plan
            $free_subscription = CampaignSubscriptionPlan::where('campaign_id', $campaign_id)->where('price', 0.0)->first();
            if ($free_subscription == null) {
                return response()->json(['error' => false, 'message' => 'campaign does not have free game play', 'data' => []], 400);
            }
            // check if audience as alreaedy been allocated free subscription
            $has_freemium_subscription = CampaignSubscription::where('campaign_id', $validated['campaign_id'])
                                                ->where('campaign_subscription_plan_id', $free_subscription->id)
                                                ->where('audience_id', $validated['audience_id'])
                                                ->first();
            if ($has_freemium_subscription) {
                return response()->json(['error' => false, 'message' => 'You must be new to this campaign to get freemium subscription', 'data' => []], 400);
            }
            // create free subscription for audience
            $new_subscription = CampaignSubscription::create([
                'campaign_id' => $campaign_id,
                'audience_id' => $validated['audience_id'],
                'campaign_subscription_plan_id' => $free_subscription->id,
                'payment_reference' => (string) Str::uuid(),
                'allocated_game_plays' => $free_subscription->game_plays,
                'available_game_plays' => $free_subscription->game_plays
            ]);
        } catch (\Exception $exception) {
            // report($th);
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'You have been allocated free game plays for this campaign', 'data' => $new_subscription], 200);
    }
}
