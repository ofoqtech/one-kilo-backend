<?php

namespace App\Services\Payments\Kashier;

use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KashierWalletPaymentStatusService
{
    public function __construct(protected KashierHttpClient $httpClient)
    {
    }

    public function reconcile(WalletTopup $topup): WalletTopup
    {
        if (! $topup->session_id) {
            throw new KashierException('Wallet topup session_id is missing', [
                'topup_id' => $topup->id,
            ]);
        }

        $response = $this->httpClient->getSessionPayment($topup->session_id);

        Log::channel((string) config('kashier.log_channel', 'stack'))
            ->info('Kashier wallet reconcile response', [
                'topup_id' => $topup->id,
                'session_id' => $topup->session_id,
                'response' => $response,
            ]);

        return $this->applyGatewayStatus($topup, $response);
    }

    protected function applyGatewayStatus(WalletTopup $topup, array $gatewayPayload): WalletTopup
    {
        $normalized = $this->normalizeGatewayPayload($gatewayPayload);
        $now = now();

        return DB::transaction(function () use ($topup, $gatewayPayload, $normalized, $now) {
            $lockedTopup = WalletTopup::query()
                ->whereKey($topup->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Already in a final state — nothing left to apply, avoid double-crediting.
            if ($lockedTopup->isFinal()) {
                $lockedTopup->update([
                    'gateway_status' => $normalized['gateway_status'] ?? $lockedTopup->gateway_status,
                    'verified_at' => $now,
                    'reconcile_payload' => $gatewayPayload,
                ]);

                return $lockedTopup->fresh();
            }

            $targetStatus = $normalized['status'];

            $topupUpdate = [
                'gateway_status' => $normalized['gateway_status'] ?? $lockedTopup->gateway_status,
                'transaction_id' => $normalized['transaction_id'] ?: $lockedTopup->transaction_id,
                'reference' => $normalized['reference'] ?: $lockedTopup->reference,
                'payment_method' => $normalized['payment_method'] ?: $lockedTopup->payment_method,
                'verified_at' => $now,
                'reconcile_payload' => $gatewayPayload,
            ];

            if ($targetStatus !== $lockedTopup->status) {
                $topupUpdate['status'] = $targetStatus;

                if ($targetStatus === WalletTopup::STATUS_PAID) {
                    $topupUpdate['paid_at'] = $now;
                } elseif (in_array($targetStatus, [WalletTopup::STATUS_FAILED, WalletTopup::STATUS_EXPIRED], true)) {
                    $topupUpdate['failed_at'] = $now;
                }
            }

            $lockedTopup->update($topupUpdate);

            if ($targetStatus === WalletTopup::STATUS_PAID) {
                $this->creditWallet($lockedTopup);
            }

            return $lockedTopup->fresh();
        });
    }

    protected function creditWallet(WalletTopup $topup): void
    {
        $wallet = Wallet::query()
            ->whereKey($topup->wallet_id)
            ->lockForUpdate()
            ->firstOrFail();

        $balanceBefore = round((float) $wallet->balance, 2);
        $amount = round((float) $topup->amount, 2);
        $balanceAfter = round($balanceBefore + $amount, 2);

        $wallet->update(['balance' => $balanceAfter]);

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $topup->user_id,
            'order_id' => null,
            'type' => WalletTransaction::TYPE_CREDIT,
            'transaction_type' => WalletTransaction::REASON_TOPUP,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => $topup->merchant_order_id,
            'notes' => 'Wallet top-up via Kashier (' . $topup->merchant_order_id . ')',
            'status' => true,
        ]);
    }

    protected function normalizeGatewayPayload(array $payload): array
    {
        $gatewayStatus = (string) (
            data_get($payload, 'status')
                ?? data_get($payload, 'payment.status')
                ?? data_get($payload, 'data.status')
                ?? data_get($payload, 'paymentStatus')
                ?? data_get($payload, 'data.paymentStatus')
                ?? ''
        );

        $status = $this->mapGatewayStatusToInternal($gatewayStatus);

        $transactionId = data_get($payload, 'transactionId')
            ?? data_get($payload, 'transaction_id')
            ?? data_get($payload, 'payment.transactionId')
            ?? data_get($payload, 'data.transactionId')
            ?? null;

        $reference = data_get($payload, 'reference')
            ?? data_get($payload, 'payment.reference')
            ?? data_get($payload, 'data.reference')
            ?? null;

        $paymentMethod = data_get($payload, 'method')
            ?? data_get($payload, 'paymentMethod')
            ?? data_get($payload, 'payment.method')
            ?? data_get($payload, 'data.method')
            ?? null;

        return [
            'status' => $status,
            'gateway_status' => $gatewayStatus !== '' ? $gatewayStatus : null,
            'transaction_id' => is_string($transactionId) ? $transactionId : null,
            'reference' => is_string($reference) ? $reference : null,
            'payment_method' => is_string($paymentMethod) ? $paymentMethod : null,
        ];
    }

    protected function mapGatewayStatusToInternal(string $gatewayStatus): string
    {
        $value = strtolower(trim($gatewayStatus));

        if ($value === '') {
            return WalletTopup::STATUS_PENDING;
        }

        if (in_array($value, ['paid', 'success', 'successful', 'captured', 'completed', 'approved'], true)) {
            return WalletTopup::STATUS_PAID;
        }

        if (in_array($value, ['expired', 'timeout'], true)) {
            return WalletTopup::STATUS_EXPIRED;
        }

        if (in_array($value, ['failed', 'failure', 'declined', 'rejected', 'canceled', 'cancelled', 'error'], true)) {
            return WalletTopup::STATUS_FAILED;
        }

        return WalletTopup::STATUS_PENDING;
    }
}
