<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Commerce\CancelOrderRequest;
use App\Http\Requests\Api\Commerce\OrderIndexRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Http\Resources\OrderResource;
use App\Services\Api\Commerce\OrderService;

class OrderController extends Controller
{
    public function __construct(protected OrderService $service) {}

    public function index(OrderIndexRequest $request)
    {
        $orders = $this->service->paginate(
            auth('sanctum')->user()->id,
            $request->filters()
        );

        return ApiResponse::sendResponse(
            200,
            __('front.orders-retrieved-successfully'),
            OrderResource::collection($orders),
            [
                'total' => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
            ]
        );
    }

    public function show(string $reference)
    {
        $order = $this->service->show(auth('sanctum')->user()->id, $reference);

        return ApiResponse::sendResponse(
            200,
            __('front.order-retrieved-successfully'),
            new OrderDetailsResource($order)
        );
    }

    public function cancel(CancelOrderRequest $request, string $reference)
    {
        $order = $this->service->cancel(
            auth('sanctum')->user()->id,
            $reference,
            $request->validated()['reason'] ?? null
        );

        return ApiResponse::sendResponse(
            200,
            __('front.order-cancelled-successfully'),
            new OrderDetailsResource($order)
        );
    }
}
