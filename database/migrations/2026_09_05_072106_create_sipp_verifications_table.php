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
        Schema::create('sipp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_case_id')->constrained('rehab_cases')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_cek');
            $table->boolean('terdaftar_rehab');
            $table->string('status_rehab', 50)->nullable();
            $table->string('id_cicilan', 100)->nullable();
            $table->string('noka_pendaftar', 30)->nullable();
            $table->string('npp_petugas', 50)->nullable();
            $table->date('tanggal_daftar_rehab')->nullable();
            $table->decimal('total_cicilan_bulan_ini', 15, 0)->nullable();
            $table->decimal('sisa_tunggakan_sipp', 15, 0)->nullable();
            $table->date('tanggal_akhir_cicilan')->nullable();
            $table->unsignedInteger('jumlah_peserta_sipp')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sipp_verifications');
    }
};
