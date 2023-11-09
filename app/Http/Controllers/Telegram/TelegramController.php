<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected $msg;
    protected $telegramService;

    public function __construct(Request $request)
    {
        if ($request->input('access_token') !== md5(config('telegram.bot.token'))) {
            abort(401);
        }

        $this->telegramService = new TelegramService();
    }

    public function webhook(Request $request)
    {
        $this->formatMessage($request->input());
        $this->handle();
        return;
    }

    private function formatMessage(array $data)
    {
        Log::info(json_encode($data));

        if (!isset($data['message'])) return;
        if (!isset($data['message']['text'])) return;

        $obj = new \StdClass();
        $text = explode(' ', $data['message']['text']);
        $obj->command = $text[0];
        $obj->args = array_slice($text, 1);
        $obj->chat_id = $data['message']['chat']['id'];
        $obj->message_id = $data['message']['message_id'];
        $obj->message_type = 'message';
        $obj->text = $data['message']['text'];
        $obj->is_private = $data['message']['chat']['type'] === 'private';

        $obj->user_id = $data['message']['from']['id'];
        if (isset($data['message']['from']['first_name'])) {
            $firstName = $data['message']['from']['first_name'];
            $obj->user_name = $firstName;
            if (isset($data['message']['from']['last_name'])) {
                $obj->user_name = $firstName . " " . $data['message']['from']['last_name'];
            }
        } else {
            if (isset($data['message']['from']['username'])) {
                $obj->user_name = $data['message']['from']['username'];
            } else {
                $obj->user_name = $data['message']['from']['id'];
            }
        }

        if (isset($data['message']['reply_to_message']['text'])) {
            $obj->message_type = 'reply_message';
            $obj->reply_text = $data['message']['reply_to_message']['text'];
        }

        // Log::info(json_encode($obj));
        $this->msg = $obj;
    }

    public function handle()
    {
        if (!$this->msg) return;
        $msg = $this->msg;
        $commandName = explode('@', $msg->command);

        // To reduce request, only commands contains @ will get the bot name
        if (count($commandName) == 2) {
            $botName = $this->getBotName();
            if ($commandName[1] === $botName){
                $msg->command = $commandName[0];
            }
        }

        try {
            if ($msg->message_type === 'message') {
                $this->fromSend();
                // 一定要双引号
                // $retText = "[$msg->user_name](tg://user?id=$msg->user_id)\n" . $msg->text;

                // $this->telegramService->sendMessage($msg->chat_id, $retText, 'markdown');

                // $reply_markup = json_encode([
                //    // 'inline_keyboard' => [
                //    //      [
                //    //          ['text' => "测试文件", 'callback_data' => '/start'],
                //    //      ]
                //    //  ]
                //     'keyboard' => [
                //         [
                //             ['text' => "按钮1"],
                //             ['text' => "按钮2"],
                //             ['text' => "按钮3"],
                //         ],
                //         [
                //             ['text' => "按钮4"],
                //             ['text' => "按钮5"],
                //         ]
                //     ],
                //     // 自适应按钮大小
                //     'resize_keyboard' => true
                // ]);

                // $this->telegramService->sendMessageMarkup($msg->chat_id, $msg->text, $reply_markup, 'markdown');
            }

            if ($msg->message_type === 'reply_message') {
                $this->telegramService->sendMessage($msg->chat_id, $msg->text);
            }
        } catch (\Exception $e) {
            $this->telegramService->sendMessage($msg->chat_id, $e->getMessage());
        }
    }

    public function getBotName()
    {
        $response = $this->telegramService->getMe();
        return $response->result->username;
    }

    private function fromSend()
    {
        switch($this->msg->command) {
            case '/start': $this->help();
                break;
            case '/getMe': $this->getMe();
                break;
            default: $this->help();
        }
    }

    private function help()
    {
        $msg = $this->msg;

        if (!$msg->is_private) return;
        $commands = [
            '/help - 帮助',
            '/getMe - 获取自己的信息'
        ];

        $text = implode(PHP_EOL, $commands);
        $this->telegramService->sendMessage($msg->chat_id, "你可以使用以下命令进行操作：\n\n$text", 'markdown');
    }

    private function getMe()
    {
        $msg = $this->msg;

        if (!$msg->is_private) return;
        $userInfo = [
            '用户ID: ' . $msg->user_id,
            '用户姓名: ' . $msg->user_name,
        ];

        // Log::info(json_encode($response));

        $text = implode(PHP_EOL, $userInfo);
        $this->telegramService->sendMessage($msg->chat_id, "当前用户信息：\n\n$text", 'markdown');
    }
}
