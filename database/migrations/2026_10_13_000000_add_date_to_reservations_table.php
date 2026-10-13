<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reservations now remember the day of play ("date").
 * Old reservations get created_at + 1 day, which is what the
 * "Day of play" line on the My Reservations page used to show.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->date('date')->nullable()->after('pitche_id');
        });

        DB::table('reservations')->whereNull('date')->orderBy('id')->each(function ($reservation) {
            $playDay = Carbon::parse($reservation->created_at ?? now())->addDay()->toDateString();

            DB::table('reservations')->where('id', $reservation->id)->update(['date' => $playDay]);
        });

        // One booking per pitch, per day, per hour: the database refuses double bookings.
        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['pitche_id', 'date', 'debut'], 'reservations_pitch_day_slot_unique');
        });
    }

    public function down(): void
    {
        // MySQL uses the unique index for the pitche_id foreign key, so the
        // foreign key is dropped first and created again at the end.
        $isMysql = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        if ($isMysql) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['pitche_id']);
            });
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_pitch_day_slot_unique');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('date');
        });

        if ($isMysql) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('pitche_id')->references('id')->on('pitches')->onDelete('cascade');
            });
        }
    }
};
