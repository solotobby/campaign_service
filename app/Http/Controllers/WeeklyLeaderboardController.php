<?php

namespace App\Http\Controllers;

use App\Helpers\GetUsers;
use App\Models\CampaignGamePlay;
use App\Services\GetBatchAudience;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WeeklyLeaderboardController extends Controller
{
    public function index()
    {
        try{
            $start_week = Carbon::now()->startOfWeek()->format('Y-m-d');
            $end_week = Carbon::now()->endOfWeek()->format('Y-m-d');
            $gameplays = CampaignGamePlay::distinct('audience_id')->select(['audience_id'])
                //  ->whereDate('created_at', '>=', $start_week)->whereDate('created_at', '<=', $end_week)
                ->get();

        $winnerPhone = '2348137331282';
        $winnerMessage = 'Winner';

        return $show = $this->sendWinnerSMS($winnerPhone, $winnerMessage);

       // return json_decode($show->getBody()->getContents(), true);
  

            $audiencesUser = (new GetBatchAudience(['user_ids' => $gameplays]))->run();
            foreach ($gameplays as $game)
            {
                $audience = collect($audiencesUser)->where('id', $game['audience_id'])->first();
                if($audience)
                {
                    $game->number_of_play = CampaignGamePlay::where('audience_id', $game['audience_id'])
                        ->whereDate('created_at', '>=', $start_week)->whereDate('created_at', '<=', $end_week)
                        ->count();
                    $game->username = $audience['username'];
                    $game->phone_number = $audience['phone_number'];
                    $game->first_name = $audience['first_name'];
                    $game->last_name = $audience['last_name'];
                }
            }
            $collection = collect($gameplays)->sortBy('number_of_play', SORT_REGULAR, true);

            $data = $collection;
        //    if($collection){
        //         $data['winner'] = $collection['0'];
        //    }

           

        }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'data' => $data], 200);
    }



    public function sendWinnerSMS($phone, $message)
    {
        try {
            $res = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://api.ng.termii.com/api/sms/send', [
                "api_key" => env('TERMI_API_KEY'),
                "message_type" => "NUMERIC",
                "to" => $phone,
                "from" => "BCToken",
                "channel" => "dnd",
                "type" => "plain",
                "sms" => $message,
            ]);

        }catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        $response = json_decode($res->getBody()->getContents(), true);
        return $response;
    }

}
