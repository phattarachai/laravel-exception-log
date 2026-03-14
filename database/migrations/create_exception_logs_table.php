<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exception_logs', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 32)->unique();
            $table->string('exception_class', 500);
            $table->text('message');
            $table->string('file', 500);
            $table->unsignedInteger('line')->nullable();
            $table->text('trace')->nullable();
            $table->json('context')->nullable();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->boolean('is_muted')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('last_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_logs');
    }
};
