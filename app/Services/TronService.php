<?php
namespace App\Services;

use IEXBase\TronAPI\Tron;

class TronService {
    protected $tron;

    public function __construct()
    {
        $this->tron = new Tron();
    }

    public function getTrxBalance(string $addr)
    {
        $contract = $this->tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

        // 获取余额
        return [
            'TRX余额: ' . $this->tron->getBalance($addr, true),
            'USDT余额: ' . $contract->balanceOf($addr),
        ];
    }
}
