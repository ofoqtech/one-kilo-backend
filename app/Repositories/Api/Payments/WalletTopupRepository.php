<?php

namespace App\Repositories\Api\Payments;

use App\Models\WalletTopup;

class WalletTopupRepository
{
    public function create(array $data): WalletTopup
    {
        return WalletTopup::query()->create($data);
    }

    public function update(WalletTopup $topup, array $data): WalletTopup
    {
        $topup->update($data);

        return $topup;
    }

    public function findById(int $id): ?WalletTopup
    {
        return WalletTopup::query()->whereKey($id)->first();
    }

    public function findBySessionId(string $sessionId): ?WalletTopup
    {
        return WalletTopup::query()
            ->where('session_id', $sessionId)
            ->latest('id')
            ->first();
    }

    public function findByMerchantOrderId(string $merchantOrderId): ?WalletTopup
    {
        return WalletTopup::query()
            ->where('merchant_order_id', $merchantOrderId)
            ->latest('id')
            ->first();
    }
}
