<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('prompt');
            $table->string('size', 20);
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->string('quality', 20);
            $table->json('reference_images')->nullable();

            $table->string('status', 20)->default('pending')->index();
            $table->string('provider', 40)->default('pixelforge');
            $table->string('provider_generation_id')->nullable()->unique();
            $table->string('provider_status', 40)->nullable();
            $table->string('error_code', 60)->nullable();
            $table->text('error_message')->nullable();

            $table->string('image_url', 2048)->nullable();
            $table->string('thumbnail_url', 2048)->nullable();

            $table->unsignedBigInteger('estimated_provider_cost_micros')->default(0);
            $table->unsignedBigInteger('estimated_user_charge_micros')->default(0);
            $table->unsignedBigInteger('actual_provider_cost_micros')->nullable();
            $table->unsignedBigInteger('actual_user_charge_micros')->nullable();
            $table->unsignedSmallInteger('markup_basis_points')->default(2500);
            $table->unsignedBigInteger('charged_micros')->default(0);

            $table->json('usage')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_provider_event_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
