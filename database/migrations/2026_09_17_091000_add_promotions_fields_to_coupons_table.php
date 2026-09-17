<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->enum('applies_to', ['subtotal', 'delivery_fee'])->default('subtotal')->after('type');
            $table->enum('delivery_discount_type', ['amount', 'percentage', 'free'])->nullable()->after('applies_to');
            $table->enum('audience', ['all', 'categories', 'regions'])->default('all')->after('status');
        });

        Schema::create('coupon_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coupon_id', 'region_id']);
        });

        Schema::create('coupon_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coupon_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_category');
        Schema::dropIfExists('coupon_region');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['applies_to', 'delivery_discount_type', 'audience']);
        });
    }
};
