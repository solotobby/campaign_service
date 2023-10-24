<?php

namespace app\Services;

use Illuminate\Support\Facades\Http;

class GetBatchAudience
{
    protected $ids;

    /**
     * __construct
     *
     * @param  mixed $ids
     * @return void
     */
    public function __construct($ids)
    {
       return $this->ids = $ids;
    }

    /**
     * run
     *
     * @return Array
     */
    public function run()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => env('AUDIENCE_URL').'/audience/get-batch',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($this->ids),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return (json_decode($response, true));//['data'];//json_decode($response, true);
    }
}
