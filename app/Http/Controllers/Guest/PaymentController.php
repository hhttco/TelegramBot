<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\AlipayService;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
	public function notify($method, Request $request)
    {
        try {
            $paymentService = new AlipayService();
            $verify = $paymentService->notify($request->input());
            if (!$verify) abort(500, 'verify error');

            Log::info("收到回调订单：" . $verify['trade_no'] . "回调：" . $verify['callback_no']);
        } catch (\Exception $e) {
            abort(500, 'fail');
        }
    }
}
