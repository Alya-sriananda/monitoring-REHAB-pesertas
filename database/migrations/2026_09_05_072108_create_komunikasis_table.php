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
        Schema::create('komunikasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_case_id')->constrained('rehab_cases')->cascadeOnDelete();
            $table->foreignId('peserta_id')->nullable()->constrained('pesertas')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_pesan_id')->nullable()->constrained('template_pesans')->nullOnDelete();
            $table->string('no_hp', 30);
            $table->date('periode_bulan');
            $table->string('template', 255)->nullable();
            $table->longText('pesan');
            $table->string('status', 50);
            $table->dateTime('tanggal_dihubungi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komunikasis');
    }
};
