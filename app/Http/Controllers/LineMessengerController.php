<?php

namespace App\Http\Controllers;

use App\LineFriend;
use App\User;
use Illuminate\Http\Request;
use LINE\LINEBot;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;

use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;

class LineMessengerController extends Controller
{
    public function __construct()
    {
        $this->http_client = new CurlHTTPClient(config('services.line.channel_token'));
        $this->messenger_secret = config('services.line.messenger_secret');
        $this->bot = new LINEBot($this->http_client, ['channelSecret' => $this->messenger_secret]);
    }

    public function webhook(Request $request) {
        // LINEから送られた内容を取得
        $request_body = $request->getContent();
        $hash = hash_hmac('sha256', $request_body, $this->messenger_secret, true);
        $signature = base64_encode($hash);

        // LINEからの送信であることを検証
        if($signature === $request->header('X-Line-Signature')) {
            $events = $this->bot->parseEventRequest($request_body, $signature);

            foreach ($events as $event) {
                $line_id = $event->getEventSourceId();
                $profile = $this->bot->getProfile($line_id)->getJSONDecodedBody();
                $lineFriend = LineFriend::findByProviderId($line_id);
                $user = null;
                if ($lineFriend->linkedSocialAccount && $lineFriend->linkedSocialAccount->user) {
                    $user = $lineFriend->linkedSocialAccount->user;
                }

                /**
                 * 入力コンテンツごとに処理
                 * + 友達追加
                 * + テキストメッセージ受信
                 * + 位置情報の受信
                 * + 選択オプション受信
                 * + ブロック
                 */
                switch (TRUE) {
                    // 友達追加
                    case $event instanceof FollowEvent:
                        if (!$lineFriend) {
                            $lineFriend = LineFriend::insert(['provider_id'=>$line_id, 'name'=>$profile['displayName']]);
                        }

                        try {
                            $user = $lineFriend->linkedSocialAccount->user;
                            $replyMessage = $user->created_at."に、会員登録もお済みです";
                            $this->reply($event, $replyMessage);
                        } catch (\Throwable $th) {
                            $replyMessage = "会員登録もよろしくお願いします。\n".route('register');
                            $this->reply($event, $replyMessage);
                        }

                        break;

                    // テキストメッセージ受信
                    case $event instanceof MessageEvent\TextMessage:
                        $text = $event->getText();
                        $replyMessage = $text.'ですね';

                        $imagePath = asset('image/line/welcome').'/';
                        $linkUrl = route('home');

                        $this->reply($event, $replyMessage);
                        // $this->image($event, $imagePath, $linkUrl); //画像の送信
                        break;

                    //位置情報の受信
                    case $event instanceof MessageEvent\LocationMessage:
                        /**
                         * $event->getTitle()
                         * $event->getAddress()
                         * $event->getLatitude()
                         * $event->getLongitude()
                         */

                        $replyMessage = $event->getAddress().'ね？';
                        $this->reply($event, $replyMessage);
                        break;

                    //選択オプション受信
                    case $event instanceof PostbackEvent:
                        break;

                    //ブロック
                    case $event instanceof UnfollowEvent:
                        break;

                    default:
                        break;
                }
            }
        }

        return 'ok';
    }

    public function reply($event, $replyMessage)
    {
        // ユーザーにメッセージを返す
        $reply = $this->bot->replyText($event->getReplyToken(), $replyMessage);

    }

    public function image($event, $path, $linkUrl = null, $alt = 'image', $width = 1040, $height = 1040, $x = 0, $y = 0)
    {
        $baseSize = new BaseSizeBuilder($height, $width); // 基本画像のサイズ
        $area = new AreaBuilder($x, $y, $width, $height);
        $imageMapActions = [ new ImagemapUriActionBuilder($linkUrl, $area)];
        $messageBuilder = new MultiMessageBuilder();

        $image_map_message = new ImagemapMessageBuilder(
            $path,
            $alt,
            $baseSize,
            $imageMapActions
        );
        $messageBuilder->Add($image_map_message);

        $reply = $this->bot->replyMessage($event->getReplyToken(), $messageBuilder);
    }

    /**
     * LINEメッセージ送信
     *
     * 友達追加されている必要がある
     */
    public function message()
    {
        // メッセージ設定
        $message = "こんにちは！";

        // LINEユーザーID指定（例）
        $lineAccount = User::find(2)->accounts()->where('provider_name', 'line')->first();
        if ($lineAccount) {

            // メッセージ送信
            $textMessageBuilder = new TextMessageBuilder($message);

            // 個別送信
            $response = $this->bot->pushMessage($lineAccount->provider_id, $textMessageBuilder);
            // マルチ送信（ID指定）
            $response = $this->bot->multicast([$lineAccount->provider_id], $textMessageBuilder);
        }
    }
}
