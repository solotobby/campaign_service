<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/', function () use ($router) {
    return $router->app->version();
});

// $router->group(['prefix' => 'api', 'middleware' => 'auth:api'], function () use ($router) {
$router->group(['prefix' => 'api'], function () use ($router) {
    // ARIEL CAMPAIGN ROUTES
    $router->group(['prefix' => 'ariel/campaign'], function () use ($router) {
        $router->get('/shoppingsites', 'ArielCampaignController@getShoppingSites');
        $router->get('/redeem/{code}', 'ArielCampaignController@redeemReferralCode');
        $router->post('/registeractivity', 'ArielCampaignController@registerInfluencingActivites');
        $router->post('/analytics', 'ArielCampaignController@analytics');
    });

    //WEEKLY LEADER-BOARD
    $router->group(['prefix' => 'weeklyleaderboard'], function () use ($router){
        $router->get('/', 'WeeklyLeaderboardController@index');
    });

    $router->group(['prefix' => 'media'], function() use ($router){
        $router->post('/', 'MediaController@storeMedia');
        $router->get('/', 'MediaController@fetchMedia');
    });

    // ARIEL CAMPAIGN ROUTES ENDS.

    //MANAGE THE GAME PLAY PRIZE
    $router->group(['prefix' => 'game/prize/'], function () use ($router){
        $router->get('/', 'CampaignGamePrizeController@index');
        $router->post('/', 'CampaignGamePrizeController@store');
    });

    //VENDOR SPIN-THE-WHEEL GAME
    $router->group(['prefix' => 'vendor'], function () use ($router) {
        $router->get('/', 'VendorSpinWheelController@index');
        $router->post('/', 'VendorSpinWheelController@store');
        $router->post('spin/{campaign_id}/play', 'VendorSpinWheelController@play');
        $router->get('spin/{campaign_id}/redeem/{audience_id}', 'VendorSpinWheelController@redeemVendorReward');
            $router->group(['prefix' => 'reward'], function () use ($router) {
                $router->post('airtime/{campaign_id}', 'VendorSpinWheelRewardController@airtimeReward');
            });
            $router->group(['prefix' => 'campaign'], function () use ($router) {
                $router->get('{vendor_id}', 'VendorCampaignController@index');
                $router->post('{vendor_id}', 'VendorCampaignController@storeCampaign');
            });
    });

    // SAFEGUARD CAMPAIGN ROUTES
    $router->group(['prefix' => 'safeguard/campaign/{campaign_id}/'], function () use ($router) {
        $router->get('/today/number-audiences', 'SafeguardCampaignController@numberAudiencesToday');
        $router->post('/game-plays/{audience_id}/start', 'SafeguardCampaignController@startNewGamePlay');
        $router->get('/game-plays/{game_play_id}/question', 'SafeguardCampaignController@getGamePlayQuestion');
        $router->get('/game-plays/{game_play_id}/data-question', 'SafeguardCampaignController@getDataCollectionQuestion');
        $router->post('/game-plays/{game_play_id}/question/{question_id}/answer', 'SafeguardCampaignController@answerGamePlayQuestion');
    });
    // SAFEGUARD CAMPAIGN ROUTES ENDS.

    $router->group(['prefix' => 'campaigns/'], function () use ($router) {
        $router->get('/active-campaigns', 'CampaignController@activeCampaigns');
        $router->get('/fetch-campaigns/{title}', 'CampaignController@fetchCampaigns');
        $router->post('/', 'CampaignController@storeInformation');
        $router->get('/', 'CampaignController@index');
        $router->group(['prefix' => '{campaign_id}/'], function () use ($router) {
            $router->get('/', 'CampaignController@show');
            $router->group(['prefix' => 'subscriptions/'], function () use ($router) {
                $router->post('/', 'CampaignGamePlayPurchaseController@create');
                $router->get('/{id}', 'CampaignGamePlayPurchaseController@show');
                $router->get('/audiences/{audience_id}', 'CampaignGamePlayPurchaseController@getLatestSubscription');
                $router->patch('/audiences/{audience_id}', 'CampaignGamePlayPurchaseController@consumeGameplay');
                $router->post('/freegameplays', 'CampaignGamePlayPurchaseController@freeGamePlays');
                $router->post('/isabisport/initialize-charge', 'IsabiSportController@initializeCharge');
                $router->post('/isabisport/charge-status', 'IsabiSportController@verifyPayment');
            });
            $router->group(['prefix' => 'referrals/'], function () use ($router) {
                $router->get('/', 'CampaignReferralController@index');
                $router->post('/', 'CampaignReferralController@create');
                $router->get('/audiences-referral-points-today', 'CampaignReferralController@referralCountForLeaderboard');
                $router->get('/{id}', 'CampaignReferralController@show');
                $router->get('/{referrer_id}/referrer', 'CampaignReferralController@showByReferrer');

                $router->group(['prefix' => 'activities'], function () use ($router) {
                    $router->post('/', 'CampaignReferralActivityController@create');
                    $router->get('/{id}', 'CampaignReferralActivityController@show');
                    $router->patch('/{referent_id}/activate-referent', 'CampaignReferralActivityController@activateReferent');
                    $router->patch('/{referrer_id}/claim-activation-point', 'CampaignReferralActivityController@updateRedeemActivationPoint');
                });
                $router->group(['prefix' => '{referral_id}/activities'], function () use ($router) {
                    $router->get('/', 'CampaignReferralActivityController@index');
                });
            });

            $router->group(['prefix' => 'leaderboards/'], function () use ($router) {
                $router->get('/daily', 'CampaignLeaderboardController@showDaily');
                $router->get('/weekly', 'CampaignLeaderboardController@showWeekly');
                $router->get('/monthly', 'CampaignLeaderboardController@showMonthly');
                $router->get('/alltime', 'CampaignLeaderboardController@showAllTime');
                $router->post('/', 'CampaignLeaderboardController@create');
            });

            $router->group(['prefix' => 'redemptions/'], function () use ($router) {
                $router->post('/{audience_id}', 'CampaignRewardRedemptionController@index');
                $router->get('/today/{audience_id}', 'CampaignRewardRedemptionController@todayWinning');
            });

            $router->group(['prefix' => 'rules/'], function () use ($router) {
                $router->get('/', 'CampaignGameRuleController@index');
                $router->post('/', 'CampaignGameRuleController@create');
                $router->get('/{rule_id}', 'CampaignGameRuleController@show');
            });

            $router->group(['prefix' => 'subscription-plans/'], function () use ($router) {
                $router->get('/', 'CampaignSubscriptionPlanController@index');
                $router->post('/', 'CampaignSubscriptionPlanController@create');
                $router->get('/{subscription_id}', 'CampaignSubscriptionPlanController@show');
            });
            $router->group(['prefix' => 'audiences/{audience_id}/'], function () use ($router) {
                $router->get('stats/lastest-game-play', 'CampaignAudienceController@leaderboardStatsLatestGamePlay');
            });
            $router->group(['prefix' => 'questions'], function () use ($router) {
                $router->get('/', 'CampaignQuestionController@index');
                $router->post('/', 'CampaignQuestionController@store');
            });
            $router->group(['prefix' => 'ad-breakers'], function () use ($router) {
                $router->post('/save-ads-time', 'CampaignAdBreakerController@saveAdsTime');
                $router->get('/save-ads-time', 'CampaignAdBreakerController@getAdsTime');
                $router->get('/', 'CampaignAdBreakerController@index');
                $router->post('/', 'CampaignAdBreakerController@store');
                $router->get('/{ad_breaker_id}', 'CampaignAdBreakerController@show');
                $router->post('/{ad_breaker_id}', 'CampaignAdBreakerController@storeActivity');

            });
            $router->group(['prefix' => 'games/'], function () use ($router) {
                $router->get('/', 'CampaignGameController@index');
                $router->post('/', 'CampaignGameController@store');
                $router->get('/{game_id}', 'CampaignGameController@show');
                $router->post('/play/start', 'CampaignGameController@startNewGamePlay');
                $router->post('/play/result', 'CampaignGameController@registerGameActivity');
            });

            ///Spin the wheel game play routes
            $router->group(['prefix' => 'spin/'], function () use ($router){
                $router->get('/', 'SpinWheelGamePlayController@initializeGameplay');
                $router->post('play', 'SpinWheelGamePlayController@play');
                $router->post('play/free', 'SpinWheelGamePlayController@playFree');
            });

            $router->group(['prefix' => 'rewards/'], function () use ($router) {
                $router->group(['prefix' => 'leaderboards/'], function () use ($router) {
                    $router->get('/', 'CampaignLeaderboardRewardController@index');
                    $router->post('/', 'CampaignLeaderboardRewardController@store');
                    $router->get('/{reward_id}', 'CampaignLeaderboardRewardController@show');
                    $router->post('/{reward_id}/audience/{audience_id}', 'CampaignLeaderboardRewardController@storeRedemption');
                });
                $router->group(['prefix' => 'instant/mobile'], function () use ($router) {
                    $router->get('/', 'CampaignMobileRewardController@index');
                    $router->post('/', 'CampaignMobileRewardController@store');
                    $router->get('/{reward_id}', 'CampaignMobileRewardController@show');
                    $router->post('/{reward_id}/audience/{audience_id}', 'CampaignMobileRewardController@storeRedemption');
                });
                $router->group(['prefix' => 'vouchers'], function () use ($router) {
                    $router->post('/upload', 'CampaignVoucherRewardController@upload');
                });

                //get airtime rewards
                $router->group(['prefix' => 'airtime/'], function () use ($router){
                    $router->get('{audience_id}', 'RewardController@AirtimeRewards');
                    $router->post('redeem/{audience_id}', 'RewardController@redeemAirtime');
                });
                //get data bundle
                $router->group(['prefix' => 'databundle/'], function () use ($router){
                    $router->get('{audience_id}', 'RewardController@DatabundleRewards');
                    $router->post('redeem/mtn/{audience_id}', 'RewardController@redeemDatabundleMTN'); // MTN databundle
                    $router->post('redeem/airtel/{audience_id}', 'RewardController@redeemDatabundleAIRTEL'); // AIRTEL databundle
                    $router->post('redeem/globacom/{audience_id}', 'RewardController@GLO'); // GLOL databundle
                    $router->post('redeem/9mobile/{audience_id}', 'RewardController@MOBILE'); // 9MOBILE databundle

                });


            });
            $router->group(['prefix' => 'game-plays/'], function () use ($router) {
                $router->post('{audience_id}/start', 'CampaignGamePlayController@startNewGamePlay');
                $router->get('{game_play_id}/question-ad', 'CampaignGamePlayController@getGamePlayQuestionAd');
                $router->post('{game_play_id}/question/{question_id}/answer', 'CampaignGamePlayController@answerGamePlayQuestion');
                $router->get('{game_play_id}/summary', 'CampaignGamePlayController@gamePlaySummary');
                $router->post('influencer/summary', 'CampaignGamePlayController@influencingSummary');
                $router->get('total/gameplays', 'CampaignGamePlayController@totalNumberOfGamePlay');
            });
            //GREEN CARD IMPLEMENTATION
            $router->group(['prefix' => 'green-card'], function () use ($router){
                $router->get('/winner-list', 'GreenCardController@winnerList');
                $router->get('/subscription-price', 'GreenCardController@getsubscriptionPrice');
                $router->get('/', 'GreenCardController@index');
                $router->post('/', 'GreenCardController@post');
                $router->get('/{audience_id}', 'GreenCardController@listAudienceSubscription');
                $router->post('/raffle-draw', 'GreenCardController@raffleDraw');

            });

        });
    });

    $router->group(['prefix' => 'subscriptions/'], function () use ($router) {
        $router->get('/audiences/{audience_id}', 'CampaignGamePlayPurchaseController@index');
    });
});
