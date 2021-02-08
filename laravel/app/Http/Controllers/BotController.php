<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use LINE\LINEBot\Constant\ActionType;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

use App\Service\Line\Message\YesNo;
use Illuminate\Support\Facades\Cache;

use App\Service\GoogleAd\AddCampaigns;
use App\Service\GoogleAd\AddAdGroups;
use App\Service\GoogleAd\AddExpandedTextAds;

class BotController extends Controller
{
    private $bot;
    private $replyToken;

    public function __construct()
    {
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(env('LINE_ACCESS_TOKEN'));
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => env('LINE_CHANNEL_SECRET')]);
    }

    /**
     * チャットボット受信処理
     *
     * @param Request $request
     * @return void
     */
    public function webhook(Request $request)
    {
        $input = $request->all();
        Log::info($input);
        // ユーザーがどういう操作を行った処理なのかを取得
        $type  = $input['events'][0]['type'];
        $this->replyToken  = $input['events'][0]['replyToken'];

        $conversation = Cache::get('conversation');

        //Conversation分岐処理
        switch ($type) {
            case ActionType::MESSAGE:
                switch ($conversation) {
                    case null:
                        switch ($input['events'][0]['message']['text']) {
                            case '広告作成':
                                $this->startCreateAd();
                                break;
                        }
                        break;
                    case 'ask_campaign_name':
                        $addCampaign = app(AddCampaigns::class);
                        $campaignId = $addCampaign->main($input['events'][0]['message']['text']);
                        $adAdGroup = app(AddAdGroups::class);
                        $adGroupId = $adAdGroup->main($campaignId);
                        $this->saySomething('広告タイトル1を入力してください。');
                        Cache::forever('conversation', 'ask_title_1');
                        Cache::forever('ad_group_id', $adGroupId);
                        break;
                    case 'ask_title_1':
                        Cache::forever('title_1', $input['events'][0]['message']['text']);
                        Cache::forever('conversation', 'ask_title_2');
                        $this->saySomething('広告タイトル2を入力してください。');
                        break;
                    case 'ask_title_2':
                        Cache::forever('title_2', $input['events'][0]['message']['text']);
                        Cache::forever('conversation', 'ask_desc');
                        $this->saySomething('説明文を入力してください。');
                        break;
                    case 'ask_desc':
                        Cache::forever('desc', $input['events'][0]['message']['text']);
                        Cache::forever('conversation', 'ask_url');
                        $this->saySomething('広告の到達先URLを入力してください。');
                        break;
                    case 'ask_url':
                        Cache::forever('url', $input['events'][0]['message']['text']);
                        $adCreation = app(AddExpandedTextAds::class);
                        $adCreation->main(
                            Cache::get('ad_group_id'),
                            Cache::get('title_1'),
                            Cache::get('title_2'),
                            Cache::get('desc'),
                            Cache::get('url')
                        );
                        Cache::forever('conversation', null);
                        $this->saySomething('広告作成完了');
                        break;
                }
                break;
            case ActionType::POSTBACK:
                switch ($input['events'][0]['postback']['data']) {
                    case 'create_ad':
                        $this->saySomething('キャンペーン名を入力してください。');
                        Cache::forever('conversation', 'ask_campaign_name');
                        break;
                    case 'stop_ad':
                        $this->saySomething('さようなら');
                        Cache::forever('conversation', null);
                        break;
                }
                break;
        }
    }

    private function startCreateAd()
    {
        $yesNo = new YesNo('広告を作成しますか？', [
            [
                'label' => '広告を作成する',
                'answer' => 'create_ad',
            ],
            [
                'label' => '広告を作成しない',
                'answer' => 'stop_ad',
            ],
        ]);
        $message = $yesNo->execute();
        $response = $this->bot->replyMessage($this->replyToken, $message);
        if ($response->isSucceeded()) {
            Log::info('Success');
            return;
        }
        Log::info('Failed');
    }

    private function saySomething(string $text)
    {
        $response = $this->bot->replyMessage($this->replyToken, new TextMessageBuilder($text));
        if ($response->isSucceeded()) {
            Log::info('Success');
            return;
        }
        Log::info('Failed');
    }
}
