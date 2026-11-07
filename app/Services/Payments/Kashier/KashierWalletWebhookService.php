<?php

namespace App\Services\Payments\Kashier;

use App\Models\WalletTopup;
use App\Repositories\Api\Payments\WalletTopupRepository;
use Illuminate\Support\Facades\Log;

class KashierWalletWebhookService
{
    public function __construct(
        protected WalletTopupRepository $walletTopupRepository,
        protected KashierWalletPaymentStatusService $paymentStatusService
    ) {
    }

    public function handle(array $payload, array $headers = [], array $query = [], ?string $rawBody = null): ?WalletTopup
    {
        $webhookLogChannel = (string) config('kashier.webhook_log_channel', config('kashier.log_channel', 'stack'));
        $safeQuery = $this->sanitizeQuery($query);

        $topup = $this->resolveTopup($payload, $query);

        if (! $topup) {
            Log::channel($webhookLogChannel)
                ->warning('Kashier wallet webhook received for unknown topup', [
                    'payload' => $payload,
                    'query' => $safeQuery,
                    'headers' => $this->sanitizeHeaders($headers),
                ]);

            return null;
        }

        $topup->update([
            'webhook_payload' => [
                'body' => $payload,
                'raw' => $rawBody,
                'query' => $safeQuery,
                'headers' => $this->sanitizeHeaders($headers),
            ],
        ]);

        Log::channel($webhookLogChannel)
            ->info('Kashier wallet webhook stored, reconciling...', [
                'topup_id' => $topup->id,
                'session_id' => $topup->session_id,
            ]);

        try {
            return $this->paymentStatusService->reconcile($topup);
        } catch (\Throwable $e) {
            Log::channel($webhookLogChannel)
                ->error('Kashier wallet webhook reconcile failed', [
                    'topup_id' => $topup->id,
                    'session_id' => $topup->session_id,
                    'message' => $e->getMessage(),
                ]);

            throw $e;
        }
    }

    protected function resolveTopup(array $payload, array $query = []): ?WalletTopup
    {
        $topupId = $query['topup_id'] ?? null;
        if (is_numeric($topupId)) {
            $topup = $this->walletTopupRepository->findById((int) $topupId);
            if ($topup) {
                return $topup;
            }
        }

        $sessionId = (string) (
            data_get($payload, 'sessionId')
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
            $query['reference'] ?? null
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

    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $values) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, ['authorization', 'cookie', 'x-api-key', 'api-key'], true)) {
                continue;
            }

            if (is_array($values)) {
                $value = implode(', ', array_map(static fn ($v) => (string) $v, $values));
            } else {
                $value = (string) $values;
            }

            $sanitized[$normalizedKey] = mb_substr($value, 0, 2000);
        }

        return $sanitized;
    }

    protected function sanitizeQuery(array $query): array
    {
        if (array_key_exists('token', $query)) {
            $query['token'] = '***';
        }

        return $query;
    }
}
