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
        Schema::create('rehab_case_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_case_id')->constrained('rehab_cases')->cascadeOnDelete();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->boolean('is_pendaftar')->default(false);
            $table->decimal('tagihan_awal', 15, 0);
            $table->unsignedInteger('jml_bulan_menunggak_awal')->nullable();
            $table->decimal('cicilan_bulanan', 15, 0);
            $table->string('data_source', 30);
            $table->timestamps();

            $table->unique(['rehab_case_id', 'peserta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rehab_case_members');
    }
};
