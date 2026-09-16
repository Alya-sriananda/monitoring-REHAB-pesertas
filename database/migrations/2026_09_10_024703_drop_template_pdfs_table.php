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
        Schema::dropIfExists('template_pdfs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('template_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_template');
            $table->string('judul');
            $table->string('subjudul')->nullable();
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
};
