<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - pitches.price_per_hour: price of one hour (in DH), set by the admin
 * - reservations.price: price paid for that hour, copied at booking time
 *   (so changing a pitch price later doesn't change old revenue)
 * - reservations.players_needed / match_note: the booker can look for teammates
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->unsignedInteger('price_per_hour')->nullable()->after('capacity');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->after('fin');
            $table->unsignedTinyInteger('players_needed')->default(0)->after('price');
            $table->string('match_note')->nullable()->after('players_needed');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['price', 'players_needed', 'match_note']);
        });

        Schema::table('pitches', function (Blueprint $table) {
            $table->dropColumn('price_per_hour');
        });
    }
};
