<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignLeaderboard;
use App\Models\CampaignGameRule;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Services\GetBatchAudience;

class CampaignLeaderboardController extends Controller
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

    public function showDaily($campaign_id)
    {
        try {
            // get campaign game rules
            $game_rules = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            // generate leaderboard
            $leaderboard = CampaignLeaderboard::where('campaign_id', $campaign_id)
                                ->whereDate('created_at', Carbon::now()->toDateString())
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->take($game_rules['leaderboard_num_winners'])
                                ->get();
            $audiencesUser = (new GetBatchAudience(['user_ids' => $leaderboard->pluck('audience_id')]))->run();
            foreach ($leaderboard as $key => $player) {
                $audience = collect($audiencesUser)->where('id', $player->audience_id)->first();
                //dd($audience);
                if ($audience) {
                    $player->username = $audience['username'];
                    $player->phone_number = $this->maskPhoneNumber($audience['phone_number']);
                    $player->position = $key + 1;
                    $player->play_durations = $this->convertMilliToSeconds($player->play_durations);
                }
            }

        } catch (\Exception $exception) {
           // report($th);
            return response()->json(['error'=>true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error'=>false, 'message' => "Daily leaderboard", 'data' => $leaderboard], 200);
    }

    /**
     * maskPhoneNumber
     *
     * @param  mixed $phone
     * @return void
     */
    public function maskPhoneNumber($phone)
    {
        $phone = (string) $phone;
        return Str::substr($phone, 0, 7).'xxxx';
    }

    public function showWeekly($campaign_id)
    {
        try {
            // get campaign game rules
            $game_rules = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            // generate leaderboard
            $start_week = Carbon::now()->startOfWeek()->format('Y-m-d');
            $end_week = Carbon::now()->endOfWeek()->format('Y-m-d');

            $leaderboard = \DB::table('campaign_leaderboards')
                                ->select('audience_id', \DB::raw('SUM(total_points) AS total_points, SUM(play_durations) AS play_durations'))
                                ->where('campaign_id', $campaign_id)
                                ->whereDate('created_at', '>=', $start_week)->whereDate('created_at', '<=', $end_week)
                                ->groupBy('audience_id')
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->take($game_rules['leaderboard_num_winners'])
                                ->get();
            // get audiences
            $audiencesUser = (new GetBatchAudience(['user_ids' => $leaderboard->pluck('audience_id')]))->run();
            foreach ($leaderboard as $key => $player) {
                $audience = collect($audiencesUser)->where('id', $player->audience_id)->first();
                //dd($audience);
                if ($audience) {
                    $player->username = $audience['username'];
                    $player->phone_number = $this->maskPhoneNumber($audience['phone_number']);
                    $player->position = $key + 1;
                    $player->play_durations = $this->convertMilliToSeconds($player->play_durations);
                }
            }
        } catch (\Exception $exception) {
            //report($th);
            return response()->json(['error'=>true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error'=>false, 'message' => "Weekly leaderboard", 'data' => $leaderboard], 200);
    }

    public function showMonthly($campaign_id)
    {
        try {
            // get campaign game rules
            $game_rules = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            // generate leaderboard
            $start_month = Carbon::now()->firstOfMonth()->format('Y-m-d');
            $end_month = Carbon::now()->lastOfMonth()->format('Y-m-d');

            $leaderboard = \DB::table('campaign_leaderboards')
                                ->select('audience_id', \DB::raw('SUM(total_points) AS total_points, SUM(play_durations) AS play_durations'))
                                ->where('campaign_id', $campaign_id)
                                ->whereDate('created_at', '>=', $start_month)->whereDate('created_at', '<=', $end_month)
                                ->groupBy('audience_id')
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->take($game_rules['leaderboard_num_winners'])
                                ->get();
            // get audiences
            $audiencesUser = (new GetBatchAudience(['user_ids' => $leaderboard->pluck('audience_id')]))->run();
            //$newAudience = $audiencesUser['data'];
            foreach ($leaderboard as $key => $player) {
                $audience = collect($audiencesUser)->where('id', $player->audience_id)->first();
                //dd($audience);
                if ($audience) {
                    $player->username = $audience['username'];
                    $player->phone_number = $this->maskPhoneNumber($audience['phone_number']);
                    $player->position = $key + 1;
                    $player->play_durations = $this->convertMilliToSeconds($player->play_durations);
                }
            }
        } catch (\Throwable $th) {
            //report($th);
            return response()->json(['error'=>true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error'=>false, 'message' => "Monthly leaderboard", 'data' => $leaderboard], 200);
    }

    public function showAllTime($campaign_id)
    {
        try {
            // get campaign game rules
            $game_rules = CampaignGameRule::where('campaign_id', $campaign_id)->first();
            // generate leaderboard
            $leaderboard = \DB::table('campaign_leaderboards')
                                ->select('audience_id', \DB::raw('SUM(total_points) AS total_points, SUM(play_durations) AS play_durations'))
                                ->where('campaign_id', $campaign_id)
                                ->groupBy('audience_id')
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->take($game_rules['leaderboard_num_winners'])
                                ->get();

            // get audiences
            $audiencesUser = (new GetBatchAudience(['user_ids' => $leaderboard->pluck('audience_id')]))->run();
            //$newAudience = $audiencesUser['data'];
            foreach ($leaderboard as $key => $player) {
                $audience = collect($audiencesUser)->where('id', $player->audience_id)->first();
                if ($audience) {
                    $player->username = $audience['username'];
                    $player->phone_number = $this->maskPhoneNumber($audience['phone_number']);
                    $player->position = $key + 1;
                    $player->play_durations = $this->convertMilliToSeconds($player->play_durations);
                }
            }
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error'=>true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error'=>false, 'message' => "All-time leaderboard", 'data' => $leaderboard], 200);
    }

    public function create(Request $request, $campaign_id)
    {
        $validated = $this->validate($request, [
            'audience_id' => 'required|string',
            'play_durations' => 'required|numeric',
            'play_points' => 'required|numeric'
        ]);

        try {
            /*
             * check if audience is already on today's leaderboard for the current campaign_id
             * if audience is on leaderboard update the record
             * else create a new leaderboard record for audience for the current campaign_id
             * */
            $leaderboard = CampaignLeaderboard::where('campaign_id', $campaign_id)
                                ->where('audience_id', $validated['audience_id'])
                                ->whereDate('created_at', date('Y-m-d'))->first();
            if ($leaderboard) {
                $leaderboard->play_durations += $validated['play_durations'];
                $leaderboard->play_points += $validated['play_points'];
                $leaderboard->total_points += $validated['play_points'];
                $leaderboard->save();
            } else {
                $leaderboard = CampaignLeaderboard::create([
                    'campaign_id' => $campaign_id,
                    'audience_id' => $validated['audience_id'],
                    'play_points' => $validated['play_points'],
                    'play_durations' => $validated['play_durations'],
                    'total_points' => $validated['play_points']
                ]);
            }

        } catch (\Throwable $th) {
           // Report($th);
            return response()->json(['error' => true, 'message' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Leaderboard record generated', 'data' => $leaderboard], 201);
    }

    public function convertMilliToSeconds($milli_sec)
    {
        return number_format(($milli_sec / 1000), 2);
    }
}
