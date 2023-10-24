<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignGamePlayPurchase;
use App\Models\CampaignQuestionActivity;
use App\Models\CampaignLeaderboard;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\CampaignGamePlay;

class CampaignAudienceController extends Controller
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
     * index
     *
     * @param  mixed $campaign_id
     * @return \Illuminate\Http\Response
     */
    public function leaderboardStatsLatestGamePlay($campaign_id, $audience_id)
    {
        try {
            $game_play = CampaignGamePlay::where('campaign_id', $campaign_id)->where('audience_id', $audience_id)->latest()->first();
            if ($game_play == null) {
                return response()->json(['error' => true, 'mesage' => 'No game play used yet'], 400);
            }
            $questions_activities = CampaignQuestionActivity::where('campaign_game_play_id', $game_play->id)->get();
            if ($questions_activities == null) {
                return response()->json(['error' => true, 'mesage' => 'no questions was answered for the last game play'], 400);
            }
            $leaderboard = CampaignLeaderboard::where('campaign_id', $campaign_id)
                                ->whereDate('created_at', Carbon::now()->toDateString())
                                ->orderBy('total_points', 'DESC')
                                ->orderBy('play_durations', 'ASC')
                                ->get();
            $total_points = 0;
            $player_position = 0;
            $duration = 0;
            if (count($leaderboard) > 0) {
                $player_record = $leaderboard->where('audience_id', $audience_id)->first();
                if ($player_record) {
                    // $total_points = $player_record->total_points;
                    // $duration = $player_record->play_durations;
                    $player_position = (int) array_search($audience_id, array_column($leaderboard->toArray(), 'audience_id')) + 1;
                } 
            }
            // if (count($leaderboard) == 0) {
            //     $player_position = null;
            // } else {
            //     $player_position = (int) array_search($audience_id, array_column($leaderboard->toArray(), 'audience_id')) + 1;
            // }

            $response = [
                'total_answered' => $questions_activities->count(),
                'total_correct_answer' => $questions_activities->where('point', '!=', 0)->count(),
                'player_leaderboard_position' => $player_position,
                'current_game_play' => 1,
                'total_points' => $questions_activities->sum('point'),
                'duration' => $questions_activities->sum('duration')
            ];
        } catch (\Throwable $th) {
            report($th);
            return response()->json(['error' => true, 'mesage' => 'something went wrong'], 500);
        }
        return response()->json(['error' => false, 'message' => 'Campaign audience stat for last game play', 'data' =>  $response], 200);
    }
}
