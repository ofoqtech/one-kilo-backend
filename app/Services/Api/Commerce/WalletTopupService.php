<?php

namespace App\Services\Api\Commerce;

use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Services\Payments\Kashier\KashierWalletService;

class WalletTopupService
{
    public function __construct(protected KashierWalletService $kashierWalletService)
    {
    }

    public function generateTopupSession(Wallet $wallet, float $amount): WalletTopup
    {
        return $this->kashierWalletService->createTopupSession($wallet, $amount);
    }
}
