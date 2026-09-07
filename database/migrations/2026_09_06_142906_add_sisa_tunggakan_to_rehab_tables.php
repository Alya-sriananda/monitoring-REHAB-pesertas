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
        Schema::table('rehab_cases', function (Blueprint $table) {
            $table->decimal('sisa_tunggakan', 15, 0)->default(0)->after('jumlah_bulan_cicilan');
        });

        Schema::table('rehab_case_members', function (Blueprint $table) {
            $table->decimal('sisa_tunggakan', 15, 0)->default(0)->after('tagihan_awal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rehab_cases', function (Blueprint $table) {
            $table->dropColumn('sisa_tunggakan');
        });

        Schema::table('rehab_case_members', function (Blueprint $table) {
            $table->dropColumn('sisa_tunggakan');
        });
    }
};
