<?php

namespace App\Http\Controllers\Api\Payments;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Api\ApiController;
use App\Models\WalletTopup;
use App\Services\Payments\Kashier\KashierWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KashierWalletController extends ApiController
{
    public function __construct(protected KashierWalletService $kashierWalletService)
    {
    }

    public function callback(Request $request)
    {
        $topup = null;

        try {
            $topup = $this->kashierWalletService->handleRedirect(
                $request->all(),
                $request->query()
            );
        } catch (\Throwable $e) {
            Log::channel((string) config('kashier.log_channel', 'stack'))
                ->error('Kashier wallet callback handling failed', [
                    'message' => $e->getMessage(),
                    'query' => $request->query(),
                ]);
        }

        $state = 'pending';
        $title = __('front.payment-result-pending-title');
        $message = __('front.payment-result-pending-message');

        if (! $topup) {
            $state = 'failed';
            $title = __('front.payment-result-unverified-title');
            $message = __('front.payment-result-unverified-message');
        } elseif ($topup->status === WalletTopup::STATUS_PAID) {
            $state = 'success';
            $title = __('front.payment-result-success-title');
            $message = __('front.payment-result-success-message');
        } elseif (in_array($topup->status, [WalletTopup::STATUS_FAILED, WalletTopup::STATUS_EXPIRED], true)) {
            $state = 'failed';
            $title = __('front.payment-result-failed-title');
            $message = __('front.payment-result-failed-message');
        } elseif ($topup->verified_at === null) {
            $state = 'pending';
            $title = __('front.payment-result-unverified-title');
            $message = __('front.payment-result-unverified-message');
        }

        $reference = $topup?->merchant_order_id;

        return response()->view('payments.kashier-result', [
            'state' => $state,
            'title' => $title,
            'message' => $message,
            'orderNumber' => $reference,
            'redirectUrl' => null,
            'buttonText' => __('front.continue'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function webhook(Request $request)
    {
        $expectedToken = (string) config('kashier.webhook_token');
        $receivedToken = (string) $request->query('token', '');

        if ($expectedToken !== '' && ! hash_equals($expectedToken, $receivedToken)) {
            Log::channel((string) config('kashier.webhook_log_channel', 'kashier_webhook'))
                ->warning('Kashier wallet webhook token mismatch', [
                    'query' => $this->sanitizeWebhookQuery($request->query()),
                ]);

            return ApiResponse::sendResponse(
                401,
                __('validation.unauthenticated'),
                []
            );
        }

        $query = $request->query();
        unset($query['token']);

        $this->kashierWalletService->handleWebhook(
            $request->all(),
            $request->headers->all(),
            $query,
            $request->getContent()
        );

        return ApiResponse::sendResponse(
            200,
            __('front.webhook-processed-successfully'),
            []
        );
    }

    protected function sanitizeWebhookQuery(array $query): array
    {
        if (array_key_exists('token', $query)) {
            $query['token'] = '***';
        }

        return $query;
    }
}
