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
        Schema::table('batches', function (Blueprint $table) {
            $table->string('file_hash')->nullable()->unique()->after('nama_file');
            $table->unsignedInteger('jumlah_row_asli')->default(0)->after('jumlah_data');
            $table->unsignedInteger('jumlah_row_valid')->default(0)->after('jumlah_row_asli');
            $table->unsignedInteger('jumlah_duplicate')->default(0)->after('jumlah_row_valid');
            $table->unsignedInteger('jumlah_conflict')->default(0)->after('jumlah_duplicate');
            $table->unsignedInteger('jumlah_invalid')->default(0)->after('jumlah_conflict');
            $table->unsignedInteger('jumlah_peserta_baru')->default(0)->after('jumlah_invalid');
            $table->unsignedInteger('jumlah_peserta_diperbarui')->default(0)->after('jumlah_peserta_baru');
            $table->string('status_proses', 50)->default('selesai')->after('catatan');
            $table->json('import_errors')->nullable()->after('status_proses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn([
                'file_hash',
                'jumlah_row_asli',
                'jumlah_row_valid',
                'jumlah_duplicate',
                'jumlah_conflict',
                'jumlah_invalid',
                'jumlah_peserta_baru',
                'jumlah_peserta_diperbarui',
                'status_proses',
                'import_errors',
            ]);
        });
    }
};
