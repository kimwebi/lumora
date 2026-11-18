<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider_event_id')->unique();
            $table->string('type', 80)->nullable();
            $table->string('status', 32)->nullable();
            $table->foreignId('generation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_generation_id')->nullable()->index();
            $table->boolean('signature_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->boolean('ignored')->default(false);
            $table->timestamp('occurred_at')->nullable();
            $table->longText('payload');
            $table->json('headers')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
