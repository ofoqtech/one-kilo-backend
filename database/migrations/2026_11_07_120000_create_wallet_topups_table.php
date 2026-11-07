<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('gateway')->index();
            $table->string('merchant_order_id')->index();
            $table->string('session_id')->nullable()->index();
            $table->text('payment_url')->nullable();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('EGP');

            $table->string('status')->index();
            $table->string('gateway_status')->nullable()->index();

            $table->string('transaction_id')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->string('payment_method')->nullable()->index();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('create_session_response')->nullable();
            $table->json('callback_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->json('reconcile_payload')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['gateway', 'session_id']);
            $table->index(['gateway', 'merchant_order_id']);
            $table->index(['wallet_id', 'gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
