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
        Schema::create('rehab_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->string('id_cicilan', 100)->nullable()->index();
            $table->string('noka_pendaftar', 30);
            $table->string('npp_petugas', 50)->nullable();
            $table->date('tanggal_pendaftaran');
            $table->unsignedInteger('jumlah_bulan_cicilan');
            $table->date('tanggal_akhir_cicilan')->nullable();
            $table->string('status_rehab', 50);
            $table->foreignId('created_from_batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->date('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rehab_cases');
    }
};
