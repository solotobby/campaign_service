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

class LeaderboardRedemptionDailyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:dailyredemption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle rewards redemption for leaderboards';

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
                            ->whereNotNull('daily_stop')
                            ->whereTime('daily_stop', '<=', (Carbon::now())->toTimeString())
                            ->get();
        if (count($campaigns) == 0) {
            // app('sentry')->captureException(new \Exception("No campaign found or it's not yet time for redemption", 1));
            return;
        }
        try {
            foreach ($campaigns as $campaign) {
                // get frequency array - Daily, weekly, monthly, all-time
                $frequencies = $this->getRedemptionFrequencies($campaign);
                // each frequency
                foreach ($frequencies as $frequency) {
                    // get reward
                    $rewards = $campaign->leaderboardRewards->where('frequency', $frequency);
                    if (count($rewards) == 0) {
                        continue;
                    }
                    // get campaign game rules
                    $game_rules = $campaign->rules;
                    // generate leaderboard
                    $leaderboards = $this->computeLeaderboard($campaign, $game_rules, $frequency);
                    if (count($leaderboards) == 0) {
                        continue;
                    }
                    app('sentry')->captureException(new \Exception("Starting ".$frequency." leaderboard redemption for ".$campaign->title, 1));
                    foreach ($rewards as $key => $reward) {
                        $players = [];
                        if ($reward->player_position) {
                            $player = $leaderboards->where('player_position', $reward->player_position)->first();
                            if (!is_null($player)) {
                                array_push($players, $player);
                            }
                        } elseif ($reward->top_players_start && $reward->top_players_end) {
                            $range = [];
                            array_push($range, $reward->top_players_start, $reward->top_players_end);
                            $players = $leaderboards->whereBetween('player_position', $range)->all();
                            if (count($players) > 0 && $reward->top_players_revenue_share_percent > 0) {
                                // make http call to get wallet revenue for the current date
                                $todayRevenue = Http::get(env('WALLET_SERVICE_URL').'/revenues/campaign/'.$campaign->id.'/daily-stats')->throw()->json();
                                $rewards[$key]->reward = (double) ($todayRevenue['hundred_percent_revenue'] * ($reward->top_players_revenue_share_percent / 100)) / (($reward->top_players_end - $reward->top_players_start) + 1);
                            }
                        }
                        foreach ($players as $player) {
                            $isRedemptionClaimed = $this->redemptionClaimed($campaign, $player, $reward);
                            if ($isRedemptionClaimed == true) {
                                continue;
                            }
                            $status = "";
                            $phone = Str::replaceFirst('0', '234', $player->audience['phone']);
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
                                app('sentry')->captureException(new \Exception("payout for audience ".$player->audience_id." dispatched", 1));
                                $job = (new PayoutJob($game_rules['payout'], $campaign->id, $player->audience_id, $reward->id, $reward->reward, $player->audience))->delay(Carbon::now()->addSeconds(30));
                                dispatch($job);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            report($th);
        }
    }

    public function getRedemptionFrequencies($campaign)
    {
        $frequencies = ['DAILY'];
        if (Carbon::today()->isSameDay(Carbon::parse($campaign->end_date))) {
            array_push($frequencies, 'ALL-TIME');
        } elseif (Carbon::today()->isLastOfMonth()) {
            array_push($frequencies, 'MONTHLY');
        } elseif (Carbon::today()->isSameDay(Carbon::now()->endOfWeek())) {
            array_push($frequencies, 'WEEKLY');
        }
        return $frequencies;
    }

    public function computeLeaderboard($campaign, $game_rules, $frequency)
    {
        $leaderboard = \DB::table('campaign_leaderboards')
                            ->select('audience_id', \DB::raw('SUM(total_points) AS total_points, SUM(play_durations) AS play_durations'))
                            ->where('campaign_id', $campaign->id)
                            ->when($frequency == 'DAILY', function ($query) {
                                return $query->whereDate('created_at', Carbon::today()->toDateString());
                            })
                            ->when($frequency == 'MONTHLY', function ($query) {
                                return $query->whereDate('created_at', '>=', Carbon::now()->firstOfMonth()->toDateString())->whereDate('created_at', '<=', Carbon::now()->lastOfMonth()->toDateString());
                            })
                            ->when($frequency == 'WEEKLY', function ($query) {
                                return $query->whereDate('created_at', '>=', Carbon::now()->startOfWeek()->toDateString())->whereDate('created_at', '<=', Carbon::now()->endOfWeek()->toDateString());
                            })
                            ->groupBy('audience_id')
                            ->orderBy('total_points', 'DESC')
                            ->orderBy('play_durations', 'ASC')
                            ->take($game_rules['leaderboard_num_winners'])
                            ->get();

        if (count($leaderboard) > 0) {
            $audiences = (new GetBatchAudience(['ids' => $leaderboard->pluck('audience_id')]))->run();
            $audiences = collect($audiences)->groupBy('id')->toArray();
            foreach ($leaderboard as $key => $player) {
                if (array_key_exists($player->audience_id, $audiences)) {
                    $leaderboard[$key]->audience = $audiences[$player->audience_id][0];  
                    $leaderboard[$key]->player_position = $key + 1;
                }
            }
        }

        return $leaderboard;
    }

    public function redemptionClaimed($campaign, $player, $reward)
    {
        $data = CampaignLeaderboardRedemption::whereDate('created_at', (Carbon::now())->toDateString())
                            ->where('audience_id', $player->audience_id)
                            ->where('campaign_id', $campaign->id)
                            ->where('campaign_leaderboard_reward_id', $reward->id)
                            ->first();
        return (is_null($data)) ? false : true;
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