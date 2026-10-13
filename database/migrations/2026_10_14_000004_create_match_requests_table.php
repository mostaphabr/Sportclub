<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Find teammates": a player asks to join someone else's booking;
 * the booker accepts or declines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | accepted | declined
            $table->string('message')->nullable();
            $table->timestamps();

            $table->unique(['reservation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_requests');
    }
};
