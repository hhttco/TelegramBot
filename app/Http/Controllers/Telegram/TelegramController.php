<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use App\Services\TronService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Users;
use Illuminate\Support\Facades\Redis;

class TelegramController extends Controller
{
    protected $msg;
    protected $telegramService;
    protected $tronService;

    public function __construct(Request $request)
    {
        if ($request->input('access_token') !== md5(config('telegram.bot.token'))) {
            abort(401);
        }

        $this->telegramService = new TelegramService();
        $this->tronService = new TronService();
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

        // if (!isset($data['message'])) return;
        if (!isset($data['message']['text']) && !isset($data['callback_query'])) return;

        $obj = new \StdClass();

        // 如果是键盘回复 可以封装一下
        if (isset($data['callback_query'])) {
            $obj->command =$data['callback_query']['data'];
            $obj->callback_query_id =$data['callback_query']['id'];
            $obj->chat_id = $data['callback_query']['message']['chat']['id'];
            $obj->user_id = $data['callback_query']['from']['id'];

            if (isset($data['callback_query']['from']['first_name'])) {
                $firstName = $data['callback_query']['from']['first_name'];
                $obj->user_name = $firstName;
                if (isset($data['callback_query']['from']['last_name'])) {
                    $obj->user_name = $firstName . " " . $data['callback_query']['from']['last_name'];
                }
            } else {
                if (isset($data['callback_query']['from']['username'])) {
                    $obj->user_name = $data['callback_query']['from']['username'];
                } else {
                    $obj->user_name = $data['callback_query']['from']['id'];
                }
            }

            $obj->message_id = $data['callback_query']['message']['message_id'];
            $obj->text = $data['callback_query']['message']['text'];
            $obj->message_type = 'message';
            $obj->is_private = $data['callback_query']['message']['chat']['type'] === 'private' ? true : false;

            $this->msg = $obj;
            // Log::info("这是键盘回复：" . json_encode($obj));
        } else {
            $text = explode(' ', $data['message']['text']);
            $obj->command = $text[0];
            $obj->args = array_slice($text, 1);
            $obj->chat_id = $data['message']['chat']['id'];

            if (isset($data['message']['chat']['title'])) {
                $obj->chat_name = $data['message']['chat']['title'];
            }
        
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

            $this->msg = $obj;
        }
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
            case '/help': $this->help();
                break;
            case '/start': $this->help();
                break;
            case '/getMe': $this->getMe();
                break;
            case '/getOne': $this->getMarkupInlineButton();
                break;
            case '/getTwo': $this->getMarkupButton();
                break;
            case '/delTwo': $this->delMarkupButton();
                break;
            case '/trxBalance': $this->getTrxBalance();
                break;
            case '/editMessage': $this->editMessage();
                break;
            case '/stopEditMessage': $this->stopEditMessage();
                break;
            default: $this->defaultFunc();
        }
    }

    private function defaultFunc()
    {
        $msg = $this->msg;

        $adText = [
            '广告',
            '营销'
        ];

        foreach ($adText as $value) {
            if (strpos($msg->text, $value) !== false) {
                $this->telegramService->sendMessage($msg->chat_id, "请不要发广告消息", '', $msg->message_id);
                $this->telegramService->deleteMessage($msg->chat_id, $msg->message_id);
                break;
            }
        }
    }

    private function help()
    {
        $msg = $this->msg;

        // if (!$msg->is_private) return;
        $commands = [
            '/help - 帮助',
            '/getMe - 获取自己的信息',
            // '/getOne - 获取按钮键盘',
            // '/getTwo - 获取键盘',
            // '/delTwo - 删除键盘',
            '/trxBalance 地址 - 获取TRX余额',
            // '/transferTrx 收款地址 转账数量 - TRX转账'
        ];

        $text = implode(PHP_EOL, $commands);
        $this->telegramService->sendMessage($msg->chat_id, "你可以使用以下命令进行操作：\n\n$text", 'markdown');
    }

    private function getMe()
    {
        $msg = $this->msg;

        $user = Users::where('telegram_id', $msg->user_id)->first();
        if (!$user) {
            // abort(500, '用户不存在');
            $user = new Users;
            $user->email = $msg->user_id . '@gmail.com';
            $user->name = $msg->user_name;
            $user->telegram_id = $msg->user_id;
            $user->password = $msg->user_id . '@gmail.com';

            $user->save();
        }

        // if (!$msg->is_private) return;
        $userInfo = [
            '系统ID: ' . $user->id,
            '用户ID: ' . $msg->user_id,
            '用户姓名: ' . $msg->user_name,
        ];

        // $userInfo = [
        //     '群组ID: ' . $msg->chat_id,
        //     '群组名称: ' . $msg->chat_name,
        // ];

        // Log::info(json_encode($msg));

        $text = implode(PHP_EOL, $userInfo);
        $this->telegramService->sendMessage($msg->chat_id, "当前用户信息：\n\n$text", 'markdown');
    }

    // 获取行内键盘
    private function getMarkupInlineButton()
    {
        $msg = $this->msg;

        $reply_markup = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "帮助", 'callback_data' => '/help'],
                    ['text' => "修改", 'callback_data' => '/editMessage'],
                ]
            ]
        ]);

        $this->telegramService->sendMessageMarkup($msg->chat_id, "获取成功", $reply_markup, 'markdown');
    }

    // 获取键盘
    private function getMarkupButton()
    {
        $msg = $this->msg;

        $reply_markup = json_encode([
            'keyboard' => [
                [
                    ['text' => "按钮1"],
                    ['text' => "按钮2"],
                    ['text' => "按钮3"],
                ],
                [
                    ['text' => "按钮4"],
                    ['text' => "按钮5"],
                ]
            ],
            // 自适应按钮大小
            'resize_keyboard' => true
        ]);

        $this->telegramService->sendMessageMarkup($msg->chat_id, "获取键盘成功", $reply_markup, 'markdown');
    }

    // 删除键盘
    private function delMarkupButton()
    {
        $msg = $this->msg;

        $reply_markup = json_encode([
            'remove_keyboard' => true
        ]);

        $this->telegramService->sendMessageMarkup($msg->chat_id, "删除键盘成功", $reply_markup, 'markdown');
    }

    // 获取TRX余额
    private function getTrxBalance()
    {
        $msg = $this->msg;

        if (!isset($msg->args[0])) {
            abort(500, '参数有误');
        }

        // 获取
        $balanceArr = $this->tronService->getTrxBalance($msg->args[0]);

        $balanceText = implode(PHP_EOL, $balanceArr);
        $this->telegramService->sendMessage($msg->chat_id, "当前：\n\n$balanceText", 'markdown');
    }

    // 收款地址 转账数量 - TRX转账
    private function transferTrx()
    {
        $msg = $this->msg;

        if (!isset($msg->args[0])) {
            abort(500, '参数有误');
        }

        if (!is_numeric($msg->args[1])) {
            abort(500, '转账金额错误');
        }

        $this->tronService->transferTrx($msg->args[0], $msg->args[1]);

        $this->telegramService->sendMessage($msg->chat_id, "操作成功", 'markdown');
    }

    // 修改行内键盘
    private function editMessage()
    {
        $msg = $this->msg;

        $reply_markup = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "帮助被修改", 'callback_data' => '/help'],
                    ['text' => "修改被修改", 'callback_data' => '/editMessage'],
                ],
                [
                    ['text' => "停止修改🤚", 'callback_data' => '/stopEditMessage'],
                ]
            ]
        ]);

        Redis::setex('edit:Message:is:stop', 20, "1");

        $reply_stop_markup = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "停止修改🤚", 'callback_data' => '/stopEditMessage'],
                ]
            ]
        ]);

        $this->telegramService->sendMessageMarkup($msg->chat_id, "手动停止", $reply_stop_markup, 'markdown');

        $titleText = $msg->text;
        for ($i = 0; $i < 10; $i++) {
            if (!Redis::get('edit:Message:is:stop')) {
                $this->telegramService->sendMessage($msg->chat_id, "🤚停止成功", 'markdown');
                break;
            }

            $titleText = $titleText . $i . "修改===被修改！！";
            $sendText = $titleText;

            if ($i < 9) {
                $sendText = $sendText . "...";
            }

            $this->telegramService->editMessageMarkup($msg->chat_id, $msg->message_id, $sendText, $reply_markup, 'markdown');

            sleep(1);
        }
    }

    private function stopEditMessage()
    {
        Redis::del('edit:Message:is:stop');
        $msg = $this->msg;
        $this->telegramService->sendMessage($msg->chat_id, "修改停止状态", 'markdown');
    }
}
