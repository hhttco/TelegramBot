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
        // 获取TRX余额
        return $this->tron->getBalance($addr, true);
    }
}
