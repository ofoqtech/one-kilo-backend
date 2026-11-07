<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Api\Commerce\OrderAnnouncementService;

class OrderObserver
{
    public function __construct(protected OrderAnnouncementService $announcementService)
    {
    }

    /**
     * Handle the Order "created" event.
     *
     * Card orders are created in "awaiting_payment" and must not reach the
     * dashboard until the payment is actually confirmed (see
     * CardPaymentFinalizationService, which announces them later). Cash
     * and wallet orders are already final/paid at creation time, so they
     * are announced immediately.
     */
    public function created(Order $order)
    {
        if (! $this->announcementService->shouldAnnounceOnCreation($order)) {
            return;
        }

        $this->announcementService->announce($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
