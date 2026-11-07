<?php

namespace App\Services\Payments\Kashier;

use App\Models\WalletTopup;
use Illuminate\Support\Arr;

class KashierWalletPayloadBuilder
{
    public function buildCreateSessionPayload(WalletTopup $topup): array
    {
        $topup->loadMissing('user');

        $expiresAt = now()
            ->addMinutes(max((int) config('kashier.session_expire_minutes', 30), 1))
            ->toIso8601String();

        $merchantRedirectUrl = $this->appendQueryParams(
            $this->resolveUrl(
                (string) config('kashier.merchant_redirect_url'),
                route('api.payments.kashier.wallet-callback')
            ),
            [
                'topup_id' => $topup->id,
                'reference' => $topup->merchant_order_id,
            ]
        );

        $serverWebhookUrl = $this->appendQueryParams(
            $this->resolveUrl(
                (string) config('kashier.server_webhook_url'),
                route('api.payments.kashier.wallet-webhook')
            ),
            [
                'topup_id' => $topup->id,
                'reference' => $topup->merchant_order_id,
                'token' => config('kashier.webhook_token'),
            ]
        );

        $payload = [
            'expireAt' => $expiresAt,
            'maxFailureAttempts' => (int) config('kashier.max_failure_attempts', 3),
            'amount' => (string) $topup->amount,
            'currency' => (string) config('kashier.currency', 'EGP'),
            'order' => (string) $topup->merchant_order_id,
            'merchantId' => (string) config('kashier.merchant_id'),
            'merchantRedirect' => $merchantRedirectUrl,
            'type' => (string) config('kashier.type', 'external'),
            'paymentType' => 'credit',
            'allowedMethods' => 'card,wallet',
            'enable3DS' => true,
            'interactionSource' => 'ECOMMERCE',

            'serverWebhook' => $serverWebhookUrl,
            'customer' => $this->buildCustomer($topup),
            'metaData' => [
                'topup_id' => (string) $topup->id,
                'wallet_id' => (string) $topup->wallet_id,
                'user_id' => (string) $topup->user_id,
            ],
            'description' => 'Wallet top-up ' . $topup->merchant_order_id,
            'display' => $this->resolveDisplay(),
        ];

        $payload = $this->removeNullValues($payload);

        $this->assertValidUrl((string) data_get($payload, 'merchantRedirect'), 'merchantRedirect');
        if (data_get($payload, 'serverWebhook')) {
            $this->assertValidUrl((string) data_get($payload, 'serverWebhook'), 'serverWebhook');
        }

        return $payload;
    }

    protected function resolveDisplay(): ?string
    {
        $display = trim((string) config('kashier.display', ''));

        return $display !== '' ? $display : null;
    }

    protected function buildCustomer(WalletTopup $topup): array
    {
        $customer = [
            'reference' => (string) ($topup->merchant_order_id . '-WT' . $topup->id),
        ];

        if ($topup->relationLoaded('user') && $topup->user) {
            $customer += [
                'name' => $topup->user->name,
                'email' => $topup->user->email,
                'phone' => $topup->user->phone,
            ];
        }

        $customer = array_filter($customer, static fn ($value) => $value !== null && $value !== '');

        return $customer;
    }

    protected function removeNullValues(array $payload): array
    {
        $payload = Arr::map($payload, function ($value) {
            if (is_array($value)) {
                return $this->removeNullValues($value);
            }

            return $value;
        });

        return array_filter($payload, static function ($value) {
            if ($value === null) {
                return false;
            }

            if (is_array($value)) {
                return $value !== [];
            }

            if (is_string($value)) {
                return trim($value) !== '';
            }

            return true;
        });
    }

    protected function resolveUrl(string $configuredValue, string $fallback): string
    {
        $configuredValue = trim($configuredValue);

        if ($configuredValue === '') {
            return $fallback;
        }

        if (preg_match('/^route\\([\'"]([^\'"]+)[\'"]\\)(\\?.*)?$/', $configuredValue, $matches) === 1) {
            try {
                $url = route($matches[1]);

                if (! empty($matches[2])) {
                    $url .= $matches[2];
                }

                return $url;
            } catch (\Throwable) {
                return $fallback;
            }
        }

        if (! str_contains($configuredValue, '://') && preg_match('/^[A-Za-z0-9_.-]+$/', $configuredValue) === 1) {
            try {
                return route($configuredValue);
            } catch (\Throwable) {
                // continue to return as-is
            }
        }

        if (str_starts_with($configuredValue, '/')) {
            return rtrim((string) config('app.url'), '/') . $configuredValue;
        }

        return $configuredValue;
    }

    protected function appendQueryParams(string $url, array $params): string
    {
        $params = array_filter($params, static fn ($value) => $value !== null && $value !== '');

        if ($params === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }

    protected function assertValidUrl(string $url, string $field): void
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        throw new KashierException('Invalid Kashier URL for ' . $field, [
            'field' => $field,
            'url' => $url,
        ]);
    }
}
