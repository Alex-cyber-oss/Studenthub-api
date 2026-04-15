<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->dateTime('reminder_time'); // Moment prévu du rappel
            $table->string('status')->default('pending'); // pending, sent, cancelled
            $table->string('time_slot')->default('20h30'); // 20h30 ou 22h
            $table->string('frequency')->default('daily'); // Fréquence (daily, twice_daily, etc.)
            $table->timestamps();
            
            $table->index(['task_id', 'status']);
            $table->index('reminder_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};
