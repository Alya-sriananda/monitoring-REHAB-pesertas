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
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->string('noka', 30)->unique();
            $table->string('nama', 200);
            $table->string('no_hp', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('alamat')->nullable();
            $table->string('status_aktif', 50)->nullable();
            $table->string('nopendaftar', 30)->nullable();
            $table->string('nopenghubung', 30)->nullable();
            $table->foreignId('daerah_id')->nullable()->constrained('daerah')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
