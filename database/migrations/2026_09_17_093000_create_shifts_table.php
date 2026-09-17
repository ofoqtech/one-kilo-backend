<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->morphs('shiftable'); // Admin (cashier) or Delivery (agent)
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->decimal('opening_cash', 10, 2)->nullable();
            $table->decimal('closing_cash', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shiftable_type', 'shiftable_id', 'started_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cashier_shift_id')->nullable()->after('delivery_id')->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_shift_id');
        });

        Schema::dropIfExists('shifts');
    }
};
