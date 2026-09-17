<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_READY = 'ready';





    public const PAYMENT_METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';
    public const PAYMENT_METHOD_CARD = 'card';
    public const PAYMENT_METHOD_WALLET = 'wallet';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';


    public const STATUS_MESSAGES = [

        self::STATUS_PENDING => [
            'title' => [
                'en' => 'Order Pending',
                'ar' => 'الطلب قيد الانتظار',
            ],
            'message' => [
                'en' => 'Your order is waiting for confirmation',
                'ar' => 'طلبك في انتظار التأكيد',
            ],
        ],

        self::STATUS_AWAITING_PAYMENT => [
            'title' => [
                'en' => 'Awaiting Payment',
                'ar' => 'في انتظار الدفع',
            ],
            'message' => [
                'en' => 'Please complete your payment to proceed',
                'ar' => 'يرجى إتمام الدفع للمتابعة',
            ],
        ],

        self::STATUS_CONFIRMED => [
            'title' => [
                'en' => 'Order Confirmed',
                'ar' => 'تم تأكيد الطلب',
            ],
            'message' => [
                'en' => 'Your order has been confirmed',
                'ar' => 'تم تأكيد طلبك',
            ],
        ],

        self::STATUS_PREPARING => [
            'title' => [
                'en' => 'Order Preparing',
                'ar' => 'جاري تحضير الطلب',
            ],
            'message' => [
                'en' => 'Your order is being prepared',
                'ar' => 'جاري تحضير طلبك',
            ],
        ],

        self::STATUS_OUT_FOR_DELIVERY => [
            'title' => [
                'en' => 'Out for Delivery',
                'ar' => 'خرج للتوصيل',
            ],
            'message' => [
                'en' => 'Your order is on the way',
                'ar' => 'طلبك في الطريق',
            ],
        ],
        self::STATUS_READY => [
            'title' => [
                'en' => 'Order Ready',
                'ar' => 'تم تجهيز الطلب',
            ],
            'message' => [
                'en' => 'Your order is ready for pickup/delivery',
                'ar' => 'تم تجهيز طلبك وهو جاهز للتسليم',
            ],
        ],
        self::STATUS_PICKED_UP => [
            'title' => [
                'en' => 'Order Picked Up',
                'ar' => 'تم استلام الطلب',
            ],
            'message' => [
                'en' => 'The delivery picked up your order',
                'ar' => 'الدليفري استلم طلبك',
            ],
        ],

        self::STATUS_DELIVERED => [
            'title' => [
                'en' => 'Order Delivered',
                'ar' => 'تم التوصيل',
            ],
            'message' => [
                'en' => 'Your order has been delivered',
                'ar' => 'تم توصيل طلبك',
            ],
        ],

        self::STATUS_CANCELED => [
            'title' => [
                'en' => 'Order Canceled',
                'ar' => 'تم إلغاء الطلب',
            ],
            'message' => [
                'en' => 'Your order has been canceled',
                'ar' => 'تم إلغاء طلبك',
            ],
        ],
    ];

    public const MESSAGE_ASSIGNED_TO_DELIVERY = [
        'title' => [
            'en' => 'New Order Assigned',
            'ar' => 'تم تعيين طلب جديد',
        ],
        'message' => [
            'en' => 'A new order has been assigned to you',
            'ar' => 'تم تعيين أوردر جديد لك',
        ],
    ];

    protected $fillable = [
        'user_id',
        'address_id',
        'coupon_id',
        'delivery_id',
        'cashier_shift_id',
        'wallet_transaction_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'total',
        'notes',
        'placed_at',
        'paid_at',
        'payment_url',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $order->statusLogs()->create([
                'old_status' => null,
                'new_status' => $order->status,
                'title' => __('dashboard.order-created'),
                'description' => __('dashboard.order-created-description', [
                    'status' => __('dashboard.order-status-' . str_replace('_', '-', $order->status)),
                ]),
                'meta' => [
                    'source' => 'system',
                ],
            ]);
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_PICKED_UP,

            self::STATUS_DELIVERED,
            self::STATUS_CANCELED,
            self::STATUS_FAILED,
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_METHOD_CASH_ON_DELIVERY,
            self::PAYMENT_METHOD_CARD,
            self::PAYMENT_METHOD_WALLET,
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_UNPAID,
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_FAILED,
            self::PAYMENT_STATUS_REFUNDED,
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            self::STATUS_PENDING => [
                self::STATUS_CONFIRMED,
                self::STATUS_CANCELED,
                self::STATUS_FAILED,
                self::STATUS_AWAITING_PAYMENT,
            ],
            self::STATUS_AWAITING_PAYMENT => [
                self::STATUS_CONFIRMED,
                self::STATUS_FAILED,
                self::STATUS_CANCELED,
            ],
            self::STATUS_CONFIRMED => [
                self::STATUS_PREPARING,
                self::STATUS_CANCELED,
            ],
            self::STATUS_PREPARING => [
                self::STATUS_READY,
                self::STATUS_OUT_FOR_DELIVERY,
                self::STATUS_CANCELED,

            ],
            self::STATUS_OUT_FOR_DELIVERY => [
                self::STATUS_DELIVERED,
                self::STATUS_PICKED_UP,
                self::STATUS_CANCELED,
            ],
            self::STATUS_PICKED_UP => [
                self::STATUS_DELIVERED,
                self::STATUS_CANCELED,
            ],
            self::STATUS_READY => [
                self::STATUS_PICKED_UP,
                self::STATUS_DELIVERED,
                self::STATUS_CANCELED,
            ],
            self::STATUS_DELIVERED => [],
            self::STATUS_CANCELED => [],
            self::STATUS_FAILED => [],
        ];
    }

    public static function allowedTransitionsFor(string $status): array
    {
        return self::statusTransitions()[$status] ?? [];
    }

    public static function salesStatuses(): array
    {
        return [
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_PICKED_UP,
            self::STATUS_DELIVERED,
        ];
    }

    public function allowedNextStatuses(): array
    {
        return self::allowedTransitionsFor($this->status);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedNextStatuses(), true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'cashier_shift_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest('created_at')->latest('id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function itemsCount(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return (int) $items->sum('quantity');
    }

    public function addressSnapshot(): array
    {
        return (array) data_get($this->meta, 'address_snapshot', []);
    }
}
