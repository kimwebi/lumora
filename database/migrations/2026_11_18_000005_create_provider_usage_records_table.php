<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_usage_records', function (Blueprint $table) {
            $table->id();
            $table->string('provider_usage_id')->unique();
            $table->string('provider_generation_id')->nullable()->index();
            $table->foreignId('generation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('image_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->unsignedBigInteger('provider_cost_micros')->default(0);
            $table->timestamp('occurred_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_usage_records');
    }
};
