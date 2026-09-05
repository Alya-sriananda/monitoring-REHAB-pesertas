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
        Schema::create('rehab_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_case_member_id')->constrained('rehab_case_members')->cascadeOnDelete();
            $table->unsignedInteger('nomor_cicilan');
            $table->date('periode_bulan')->index();
            $table->decimal('besaran_cicilan', 15, 0);
            $table->date('tanggal_bayar')->nullable()->index();
            $table->timestamps();

            $table->unique(['rehab_case_member_id', 'periode_bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rehab_installments');
    }
};
