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
        $table->id();
        $table->foreignId('event_type_id')
            ->constrained()
            ->cascadeOnDelete();
        $table->foreignId('created_by')
            ->constrained('users');
        $table->string('title');
        $table->text('description')->nullable();
        $table->dateTime('start_datetime');
        $table->dateTime('end_datetime')->nullable();
        $table->string('status')->default('Scheduled');
        $table->string('priority')->default('Normal');
        $table->string('location')->nullable();
        $table->timestamps();
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
