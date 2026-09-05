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
        Schema::create('template_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_template', 150);
            $table->string('judul', 255);
            $table->string('subjudul', 255)->nullable();
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->json('field_config');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_pdfs');
    }
};
