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
        if (!isset($data['message'])) return;
        if (!isset($data['message']['text'])) return;

        Log::info(json_encode($data));

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

        Log::info(json_encode($obj));
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
                $this->telegramService->sendMessage($msg->chat_id, $msg->text);

                $retText = '[$msg->user_name](tg://user?id=$msg->user_id) ' . $msg->text;
                $reply_markup = json_encode([
                   'inline_keyboard' => [
                        [
                            ['text' => $retText, 'callback_data' => '/start'],
                        ]
                    ]
                ]);

                $this->telegramService->sendMessageMarkup($msg->chat_id, $msg->text, $reply_markup, 'markdown');
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
}
