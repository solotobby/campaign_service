<?php 

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class GetUsers{

    public static function fetchUserInfo($gameplays)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application'
        ])->post(env('AUDIENCE_URL').'/audience/get-batch', $gameplays)->throw();

        return $response;

    }
}