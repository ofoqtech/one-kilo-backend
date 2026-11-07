<?php

namespace App\Services\Api\Commerce;

use App\Exceptions\ApiBusinessException;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Repositories\Api\Commerce\WalletRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WalletService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected WalletTopupService $walletTopupService
    ) {
    }

    public function current(int $userId): Wallet
    {
        return $this->walletRepository->getOrCreateForUser($userId);
    }

    public function paginateTransactions(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $this->walletRepository->getOrCreateForUser($userId);

        return $this->walletRepository->paginateTransactions($userId, $perPage);
    }

    public function debitForOrder(int $userId, Order $order, float $amount): WalletTransaction
    {
        $wallet = $this->resolveWalletForPayment($userId);

        if (! $wallet->status) {
            throw new ApiBusinessException(
                __('front.wallet-inactive'),
                422,
                ['wallet' => [__('front.wallet-inactive')]]
            );
        }

        $balanceBefore = round((float) $wallet->balance, 2);
        $amount = round($amount, 2);

        if ($balanceBefore < $amount) {
            throw new ApiBusinessException(
                __('front.wallet-insufficient-balance'),
                422,
                ['wallet' => [__('front.wallet-insufficient-balance')]]
            );
        }

        $balanceAfter = round($balanceBefore - $amount, 2);

        $this->walletRepository->updateBalance($wallet, $balanceAfter);

        return $this->walletRepository->createTransaction([
            'wallet_id' => $wallet->id,
            'user_id' => $userId,
            'order_id' => $order->id,
            'type' => WalletTransaction::TYPE_DEBIT,
            'transaction_type' => WalletTransaction::REASON_ORDER_PAYMENT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $order->order_number,
            'notes' => 'Wallet payment for order ' . $order->order_number,
            'status' => true,
        ])->load('order');
    }

    public function refreshWallet(Wallet $wallet): Wallet
    {
        return $this->walletRepository->loadDetails($wallet->fresh());
    }

    public function initiateTopup(int $userId, float $amount): WalletTopup
    {
        $wallet = $this->walletRepository->getOrCreateForUser($userId);

        if (! $wallet->status) {
            throw new ApiBusinessException(
                __('front.wallet-inactive'),
                422,
                ['wallet' => [__('front.wallet-inactive')]]
            );
        }

        return $this->walletTopupService->generateTopupSession($wallet, $amount);
    }

    private function resolveWalletForPayment(int $userId): Wallet
    {
        $wallet = $this->walletRepository->findForUser($userId, true);

        if ($wallet) {
            return $wallet;
        }

        $this->walletRepository->getOrCreateForUser($userId);

        return $this->walletRepository->findForUser($userId, true);
    }
}
