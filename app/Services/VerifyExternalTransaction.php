<?php

namespace app\Services;

use App\Models\TransactionChannel;
use Illuminate\Support\Facades\Http;

class VerifyExternalTransaction
{
    protected $channel_id;
    protected $payment_ref;
    
    /**
     * __construct
     *
     * @param  mixed $channel_id
     * @param  mixed $payment_ref
     * @return void
     */
    public function __construct($channel_id, $payment_ref)
    {
        $this->channel_id = $channel_id;
        $this->payment_ref = $payment_ref; 
    }
    
    /**
     * run
     *
     * @return Array
     */
    public function run()
    {
        // Get which channel to verify payment
        $channel = TransactionChannel::findOrFail($this->channel_id);
        if ($channel->name == "ISABI_SPORT") {
            return $this->verifyIsabiSport();
        }
    }
    
    /**
     * verifyIsabiSport
     *
     * @return Array
     */
    public function verifyIsabiSport()
    {
        $build_url = env('ISABI_SPORT_BASE_URL')."init-charge/".$this->payment_ref;
        $response = Http::withHeaders(['Authorization' => env('ISABI_SPORT_AUTHORIZATION_KEY')])->get($build_url)->throw()->json(); 
        return [
            'amount' => ($response['response']['amount']) / 100,
            'is_pending' => $response['response']['status'] == 'success' ? false : true,
            'reference' => $response['response']['reference']
        ];
    }
}