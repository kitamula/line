<?php

namespace App\Http\Controllers;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot;
use App\User;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use Illuminate\Http\Request;

use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;

class LineMessengerController extends Controller
{
    public function webhook(Request $request) {
        // LINEから送られた内容を$inputsに代入
        $inputs = $request->all();

        $events = $inputs['events'];
        foreach ($events as $event) {
            $message_type = $event['type'];
            if($message_type == 'message') {
                $this->reply($event);
            }
        }

        return 'ok';
    }

    public function reply($event)
    {
        $reply_token = $event['replyToken'];
        $message = $event['message']['text'];

        // LINEBOTSDKの設定
        $http_client = new CurlHTTPClient(config('services.line.channel_token'));
        $bot = new LINEBot($http_client, ['channelSecret' => config('services.line.messenger_secret')]);

        // 送信するメッセージの設定
        $reply_message = 'メッセージありがとうございます'.$message;

        // ユーザーにメッセージを返す
        $reply = $bot->replyText($reply_token, $reply_message);

    }

    /**
     * LINEメッセージ送信
     *
     * 友達追加されている必要がある
     */
    public function message() {

        // LINEBOTSDKの設定
        $http_client = new CurlHTTPClient(config('services.line.channel_token'));
        $bot = new LINEBot($http_client, ['channelSecret' => config('services.line.messenger_secret')]);

        // メッセージ設定
        $message = "こんにちは！";

        // LINEユーザーID指定
        $lineAccount = User::find(2)->accounts()->where('provider_name', 'line')->first();
        if ($lineAccount) {

            // メッセージ送信
            $textMessageBuilder = new TextMessageBuilder($message);
            $response    = $bot->pushMessage($lineAccount->provider_id, $textMessageBuilder);
        }


    }
}
