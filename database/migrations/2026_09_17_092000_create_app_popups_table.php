<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_popups', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->string('image');
            $table->enum('link_type', ['none', 'product', 'category', 'coupon', 'url'])->default('none');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->string('link_url')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_popups');
    }
};
