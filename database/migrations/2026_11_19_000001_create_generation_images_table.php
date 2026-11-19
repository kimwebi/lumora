<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mime', 64);
            $table->string('extension', 8);
            $table->unsignedBigInteger('bytes');
            $table->string('checksum', 64);
            // Base64 keeps binary data portable across MySQL, PostgreSQL and SQLite.
            $table->longText('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_images');
    }
};
