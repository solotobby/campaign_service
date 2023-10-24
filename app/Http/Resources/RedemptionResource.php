<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class RedemptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'campaign_id' => $this->campaign_id,
            'audience_id' => $this->audience_id,
            'status' => $this->status,
            'reward' => $this->reward_value,
            'reward_type' => $this->campaignLeaderboardReward['type'],
            'reward_description' => $this->rewardDescription(),
            'created_at' => $this->created_at
        ];
    }

    public function rewardDescription()
    {
        $description = "";
        if ($this->campaignLeaderboardReward['player_position'] != 0) {
            $description = "Top ".$this->campaignLeaderboardReward['player_position']." winner";
        } elseif ($this->campaignLeaderboardReward['top_players_end'] != 0) {
            $description = "Top ".$this->campaignLeaderboardReward['top_players_end']." winner";
        }
        return $description;
    }
}