<?php

namespace App\Services;

use App\Plugins\Payments\AlipayF2F;

class AlipayService {
    protected $config = [];

    public function __construct()
    {
        $this->config['app_id']      = config('pay.alipay.app_id');
        $this->config['private_key'] = config('pay.alipay.private_key');
        $this->config['public_key']  = config('pay.alipay.public_key');
    }

    public function pay($order)
    {
        try {
            $gateway = new AlipayF2F();
            $gateway->setMethod('alipay.trade.precreate');
            $gateway->setAppId($this->config['app_id']);
            $gateway->setPrivateKey($this->config['private_key']); // 可以是路径，也可以是密钥内容
            $gateway->setAlipayPublicKey($this->config['public_key']); // 可以是路径，也可以是密钥内容
            $gateway->setNotifyUrl($order['notify_url']);

            $gateway->setBizContent([
                'subject'      => $order['trade_no'],
                'out_trade_no' => $order['trade_no'],
                'total_amount' => $order['total_amount'] / 100
            ]);
            $gateway->send();

            return [
                'type' => 0, // 0:qrcode 1:url
                'data' => $gateway->getQrCodeUrl()
            ];
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function notify($params)
    {
        if ($params['trade_status'] !== 'TRADE_SUCCESS') return false;
        $gateway = new AlipayF2F();
        $gateway->setAppId($this->config['app_id']);
        $gateway->setPrivateKey($this->config['private_key']); // 可以是路径，也可以是密钥内容
        $gateway->setAlipayPublicKey($this->config['public_key']); // 可以是路径，也可以是密钥内容
        try {
            if ($gateway->verify($params)) {
                /**
                 * Payment is successful
                 */
                return [
                    'trade_no'    => $params['out_trade_no'],
                    'callback_no' => $params['trade_no']
                ];
            } else {
                /**
                 * Payment is not successful
                 */
                return false;
            }
        } catch (\Exception $e) {
            /**
             * Payment is not successful
             */
            return false;
        }
    }
}
