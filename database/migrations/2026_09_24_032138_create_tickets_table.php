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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained();
            $table->date('date');
            $table->unsignedInteger('number');
            $table->string('session_id');
            $table->string('status')->default('waiting');
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamps();

            // Safety net behind the locked queue_counters increment: no duplicate numbers per service per day.
            $table->unique(['service_id', 'date', 'number']);
            // "Call Next": oldest waiting ticket for a service.
            $table->index(['service_id', 'status']);
            // "Does this browser already hold an active ticket for this service?"
            $table->index(['session_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
