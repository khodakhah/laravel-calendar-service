<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('calendar_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_event_id')->nullable()->index();
            $table->uuid('series_master_event_id')->nullable()->index();
            $table->string('event_number', 100)->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('revision')->default(1);
            $table->string('type', 32)->default('single')->index();
            $table->string('timezone', 64);
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('all_day')->default(false);
            $table->boolean('blocks_availability')->default(true);
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('buffer_before_minutes')->default(0);
            $table->unsignedInteger('buffer_after_minutes')->default(0);
            $table->json('sources')->nullable();
            $table->json('assignees')->nullable();
            $table->json('created_by')->nullable();
            $table->json('updated_by')->nullable();
            $table->string('updated_reason', 500)->nullable();
            $table->json('location')->nullable();
            $table->json('recurrence')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['calendar_id', 'starts_at']);
            $table->index(['calendar_id', 'blocks_availability', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
