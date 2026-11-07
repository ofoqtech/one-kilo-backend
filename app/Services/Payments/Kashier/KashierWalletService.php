<?php

namespace App\Services\Payments\Kashier;

use App\Exceptions\ApiBusinessException;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Repositories\Api\Payments\WalletTopupRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KashierWalletService
{
    public function __construct(
        protected WalletTopupRepository $walletTopupRepository,
        protected KashierWalletSessionService $sessionService,
        protected KashierWalletPaymentStatusService $paymentStatusService,
        protected KashierWalletWebhookService $webhookService
    ) {
    }

    public function createTopupSession(Wallet $wallet, float $amount): WalletTopup
    {
        if (! config('kashier.enabled')) {
            throw new ApiBusinessException(
                __('front.payment-gateway-unavailable'),
                422,
                ['amount' => [__('front.payment-gateway-unavailable')]]
            );
        }

        $topup = $this->walletTopupRepository->create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'gateway' => WalletTopup::GATEWAY_KASHIER,
            'merchant_order_id' => $this->generateMerchantOrderId(),
            'amount' => round($amount, 2),
            'currency' => (string) config('kashier.currency', 'EGP'),
            'status' => WalletTopup::STATUS_PENDING,
            'meta' => [
                'source' => 'wallet_topup',
            ],
        ]);

        try {
            return $this->sessionService->createTopupSession($topup);
        } catch (\Throwable $e) {
            Log::channel((string) config('kashier.log_channel', 'stack'))
                ->error('Wallet topup session creation failed', [
                    'topup_id' => $topup->id,
                    'message' => $e->getMessage(),
                ]);

            throw new ApiBusinessException(
                __('front.payment-gateway-unavailable'),
                422,
                ['amount' => [__('front.payment-gateway-unavailable')]]
            );
        }
    }

    public function handleRedirect(array $payload, array $query = []): WalletTopup
    {
        $topup = $this->resolveTopupFromCallback($payload, $query);

        if (! $topup) {
            throw new ApiBusinessException(
                __('validation.resource-not-found'),
                404,
                []
            );
        }

        $topup->update([
            'callback_payload' => [
                'body' => $payload,
                'query' => $query,
            ],
        ]);

        Log::channel((string) config('kashier.log_channel', 'stack'))
            ->info('Kashier wallet callback stored, reconciling...', [
                'topup_id' => $topup->id,
                'session_id' => $topup->session_id,
            ]);

        try {
            return $this->paymentStatusService->reconcile($topup);
        } catch (\Throwable $e) {
            Log::channel((string) config('kashier.log_channel', 'stack'))
                ->error('Kashier wallet callback reconcile failed', [
                    'topup_id' => $topup->id,
                    'session_id' => $topup->session_id,
                    'message' => $e->getMessage(),
                ]);

            return $topup->fresh();
        }
    }

    public function handleWebhook(array $payload, array $headers = [], array $query = [], ?string $rawBody = null): ?WalletTopup
    {
        return $this->webhookService->handle($payload, $headers, $query, $rawBody);
    }

    protected function generateMerchantOrderId(): string
    {
        return 'WT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    protected function resolveTopupFromCallback(array $payload, array $query = []): ?WalletTopup
    {
        $topupId = $query['topup_id'] ?? $payload['topup_id'] ?? null;

        if (is_numeric($topupId)) {
            $topup = $this->walletTopupRepository->findById((int) $topupId);
            if ($topup) {
                return $topup;
            }
        }

        $sessionId = (string) (
            $query['sessionId'] ?? $query['session_id'] ?? null
                ?? data_get($payload, 'sessionId')
                ?? data_get($payload, 'session_id')
                ?? data_get($payload, 'data.sessionId')
                ?? ''
        );

        if ($sessionId !== '') {
            $topup = $this->walletTopupRepository->findBySessionId($sessionId);
            if ($topup) {
                return $topup;
            }
        }

        $merchantOrderId = (string) (
            $query['reference'] ?? $query['orderId'] ?? $query['order_id'] ?? null
                ?? data_get($payload, 'orderId')
                ?? data_get($payload, 'order_id')
                ?? data_get($payload, 'data.orderId')
                ?? ''
        );

        if ($merchantOrderId !== '') {
            return $this->walletTopupRepository->findByMerchantOrderId($merchantOrderId);
        }

        return null;
    }
}
