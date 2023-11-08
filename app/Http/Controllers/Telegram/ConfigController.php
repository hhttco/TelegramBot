<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;

class ConfigController extends Controller
{
    // setWebhook
    public function setWebhook()
    {
        $hookUrl = url('/telegram/webhook?access_token=' . md5(config('telegram.bot.token')));
        $telegramService = new TelegramService();
        $telegramService->setWebhook($hookUrl);

        return response([
            'data' => true
        ]);
    }
}
