<?php

namespace app\Services;

use Illuminate\Support\Facades\Http;

class AirtimeService
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
        $this->ids = $ids;
    }

    public function run()
    {
        //dd($this->ids);

//        $res = Http::withHeaders([
//            'Accept' => 'application/json',
//            'Content-Type' => 'application/json',
//            'apiKey' => '8cdd129d84a3c6eb68790d532c8699a18a7a53dc7212173225a9e4c3a81ba9d4'
//            //'Authorization' => 'Bearer 8cdd129d84a3c6eb68790d532c8699a18a7a53dc7212173225a9e4c3a81ba9d4'//.env('FLUTTERWAVE_SECRET_KEY')
//        ])->post('https://api.sandbox.africastalking.com/version1/airtime/send', [
//            "username" => "Proxima",
//           // "apiKey" => "8cdd129d84a3c6eb68790d532c8699a18a7a53dc7212173225a9e4c3a81ba9d4",
//            "phoneNumber"  => "+2348137331282",
//            //"currencyCode" => "NGN",
//            "amount" => "NGN 100.00"
//        ]);
//        $response = json_decode($res->getBody()->getContents(), true);
//
//        return $response;
    }

}
