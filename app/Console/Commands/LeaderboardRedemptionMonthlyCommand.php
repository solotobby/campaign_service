<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\RedemptionActivity;
use App\Models\Campaign;
use App\Models\CampaignLeaderboard;
use App\Models\CampaignLeaderboardRedemption;
use Illuminate\Support\Facades\Http;
use App\Jobs\PayoutJob;
use App\Jobs\AirtimeJob;
use App\Jobs\SmsJob;
use App\Services\GetBatchAudience;

class LeaderboardRedemptionMonthlyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:monthlyredemption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle monthly rewards for leaderboards';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $campaigns = Campaign::whereDate('start_date', '<=', (Carbon::now())->toDateString())
                            ->whereDate('end_date', '>=', (Carbon::now())->toDateString())
                            ->get();
        if (count($campaigns) == 0) {
            return;
        }
        foreach ($campaigns as $campaign) {
            // get campaign game rules
            $game_rules = $campaign->rules;

            // generate leaderboard
            $start_month = Carbon::now()->firstOfMonth()->format('Y-m-d');
            $end_month = Carbon::now()->lastOfMonth()->format('Y-m-d');

            $leaderboard = \DB::table('campaign_leaderboards')
                                ->select('audience_id', \DB::raw('SUM(total_points) AS total_points, SUM(play_durations) AS play_durations'))
                                ->where('campaign_id', $campaign->id)
                                ->whereDate('created_at', '>=', $start_month)->whereDate('created_at', '<=', $end_month)
                                ->groupBy('audience_id')
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->take($game_rules['leaderboard_num_winners'])
                                ->get();
            // get audiences
            if (count($leaderboard) > 0) {
                $audiences = (new GetBatchAudience(['ids' => $leaderboard->pluck('audience_id')]))->run();
                $audiences = collect($audiences)->groupBy('id')->toArray();
            }

            foreach ($leaderboard as $key => $player) {
                if (array_key_exists($player->audience_id, $audiences)) {
                    $audience = $audiences[$player->audience_id][0];  
                    // get reward
                    $reward = $campaign->leaderboardRewards->where('frequency', 'MONTHLY')
                                        ->where('player_position', $key + 1)
                                        ->first();
                    if ($reward) {
                        // check if redemption activity exist for audience
                        $redemption = CampaignLeaderboardRedemption::whereDate('created_at', (Carbon::now())->toDateString())
                                            ->where('audience_id', $player->audience_id)
                                            ->where('campaign_id', $campaign->id)
                                            ->where('campaign_leaderboard_reward_id', $reward->id)
                                            ->first();

                        if ($redemption == null) {
                            try {
                                $status = "";
                                $phone = Str::replaceFirst('0', '234', $audience['phone']);
                                if ($reward->type == "AIRTIME") {
                                    $callBackUrl = env('APP_URL')."/campaigns/".$campaign->id."/rewards/leaderboards/".$reward->id."/audience/".$player->audience_id;
                                    $job = (new AirtimeJob($phone, (double) $reward->reward, $callBackUrl, 'primary', $player->audience_id,'proxima'))->delay(Carbon::now()->addSeconds(20));
                                    dispatch($job);
                                } elseif ($reward->type == "VOUCHER") {
                                    $msg = "Congratulations! You've won N".$reward['reward']." cash from Arena! Message us on https://www.instagram.com/arena_afrika/ to redeem!";
                                    // store redemption activity
                                    $redemption_activity = CampaignLeaderboardRedemption::create([
                                        'campaign_id' => $campaign->id,
                                        'campaign_leaderboard_reward_id' => $reward->id,
                                        'audience_id' => $player->audience_id,
                                        'status' => "SUCCESS"
                                    ]);
                                    // send sms to user about winning and board position
                                    $job = (new SmsJob($phone, $msg, 'dnd', 'primary', 'Topbrain', $player->audience_id))->delay(Carbon::now()->addSeconds(20));
                                    dispatch($job);
                                } elseif ($reward->type == "CASH" && !is_null($game_rules['payout'])) {
                                    $job = (new PayoutJob($game_rules['payout'], $campaign->id, $player->audience_id, $reward, $audience))->delay(Carbon::now()->addSeconds(30));
                                    dispatch($job);
                                }
                            } catch (\Throwable $th) {
                                report($th);
                            }
                        }
                    }
                }
            }
        }
    }

    public function bancoreAirtime($phone, $operator, $amount)
    {
        $orderID = uniqid();

        $url = 'https://www.kegow.com/getit/api/merchant/merchantairtime.do?' .
        'merchantID=' . 854 .
        '&operator=' . $operator .
        '&destinationNumber=' . $phone .
        '&amount=' . $amount * 100 .
        '&orderId=' .  $orderID .
        '&currency=NGN' .
        '&encKey=' . hash('sha256', 854 . $phone . $amount * 100 .  $orderID . $operator . "0D8DB87D7E8231E27FE7C33CF656162B");

       $response = Http::withHeaders(['Content-Type' => 'application/json'])->get($url);
        if (strpos($response->body(), 'result=30') !== false) {
            return true;
        } else {
            throw new \Exception(json_encode([
                'action' => 'leaderboard|airtime',
                'status' => 'failed',
                'phone' => $phone,
                'operator' => $operator,
                'amount' => $amount
                ]));
            return false;
        }
    }

    public function termiiSendMessage($phone, $message)
    {
        return Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ])->post('https://termii.com/api/sms/send', [
                        "api_key" => "TLzznjOjXVewl3hWOvnX4i9hf2M7iV352McbCcjqMFRJmw9O0I9CxMFMynE9zt",
                        "to" => $phone,
                        "from" => "Top Brain",
                        "sms" => $message,
                        "type" => "plain",
                        "channel" => "generic"
                    ])->throw()->json();
    }
}