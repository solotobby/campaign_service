<?php

namespace App\Jobs;
use Illuminate\Support\Facades\Http;
use App\Models\CampaignLeaderboardRedemption;

class PayoutJob extends Job
{
    protected $campaignId;
    protected $audienceId;
    protected $rewardId;
    protected $rewardValue;
    protected $bankDetails;
    protected $paymentType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($paymentType, $campaignId, $audienceId, $rewardId, $rewardValue, $bankDetails=[])
    {
        $this->campaignId = $campaignId;
        $this->audienceId = $audienceId;
        $this->rewardValue = $rewardValue;
        $this->rewardId = $rewardId;
        $this->bankDetails = $bankDetails;
        $this->paymentType = $paymentType;
        $this->onQueue(env('AWS_SQS_PAYOUT_QUEUE'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app('sentry')->captureException(new \Exception("payout handled for audience ".$this->audienceId, 1));
        try {
            if ($this->paymentType == 'wallet') {
                $this->processWalletPayout();
            } elseif ($this->paymentType == 'bank' && count($this->bankDetails) > 0 && !is_null($this->bankDetails['bank_code']) && !is_null($this->bankDetails['bank_number'])) {
                $this->processBankPayout();
            }
        } catch (\Throwable $th) {
            report($th);
        }
    }

    public function processWalletPayout()
    {
        $payload = [
            'wallet_id' => $this->audienceId,
            'amount' => $this->rewardValue,
            'platform' => 'arena', //update this later by getting platform name from .env
            'trans_type' => 'leaderboard-winnings',
            'reference' => $this->campaignId
        ];
        $response = Http::post(env('WALLET_SERVICE_URL').'/credit', $payload);
        if ($response->serverError() || $response->clientError()) {
            throw new \Exception(json_encode($response->json()), $response->status());
        } elseif ($response->successful()) {
            $this->storeRedemptionActivity($response->json()['id'], 'SUCCESS');
        }
    }

    public function processBankPayout()
    {
        $build_url = env('ISABI_SPORT_BASE_URL')."withdraw?bank=".$this->bankDetails['bank_code']."&account=".$this->bankDetails['bank_number']."&amount=".$this->rewardValue."&description=Payout";
        $verifiedPayment = Http::withHeaders(['Authorization' => env('ISABI_SPORT_AUTHORIZATION_KEY')])->post($build_url);
        if ($verifiedPayment->failed()) {
            throw new \Exception($verifiedPayment->body(), $verifiedPayment->status());
        }
        $verifiedPayment = $verifiedPayment->json();
        $status = ($verifiedPayment['response']['status'] == 'NEW') ? 'SUCCESS' : 'PENDING';
        $this->storeRedemptionActivity($verifiedPayment['response']['reference'], $status);
    }

    public function storeRedemptionActivity($ref, $status)
    {
        CampaignLeaderboardRedemption::create([
            'campaign_id' => $this->campaignId,
            'campaign_leaderboard_reward_id' => $this->rewardId,
            'reward_value' => $this->rewardValue,
            'audience_id' => $this->audienceId,
            'cash_payment_ref' => $ref,
            'status' => $status
        ]);
    }
}
