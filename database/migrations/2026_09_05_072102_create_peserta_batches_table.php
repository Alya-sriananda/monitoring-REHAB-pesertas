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
        Schema::create('peserta_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->string('data_source', 30)->default('excel');

            // Snapshot fields
            $table->string('statusaktif')->nullable();
            $table->string('total_peserta')->nullable();
            $table->string('zero')->nullable();
            $table->text('alamat')->nullable();
            $table->string('bulan_menunggak')->nullable();
            $table->string('cabang_kd')->nullable();
            $table->string('divre_kd')->nullable();
            $table->string('email')->nullable();
            $table->string('endcicilan')->nullable();
            $table->string('idcicilan')->nullable();
            $table->string('index_data')->nullable();
            $table->string('jmlbulancicilawal')->nullable();
            $table->string('jmlbulanmenunggakawal')->nullable();
            $table->string('kanal_pendaftaran')->nullable();
            $table->string('kantor_cabang')->nullable();
            $table->string('kddati2')->nullable();
            $table->string('kddesa')->nullable();
            $table->string('kdkec')->nullable();
            $table->string('kelas')->nullable();
            $table->string('kelas_group')->nullable();
            $table->string('namaentitas')->nullable();
            $table->string('nmdati2')->nullable();
            $table->string('nmdesa')->nullable();
            $table->string('nmkc')->nullable();
            $table->string('nmkec')->nullable();
            $table->string('noentitas')->nullable();
            $table->string('nohp')->nullable();
            $table->string('nopendaftar')->nullable();
            $table->string('nopenghubung')->nullable();
            $table->string('startcicilan')->nullable();
            $table->string('tanggalupdatedata')->nullable();
            $table->string('tglcicilan')->nullable();
            $table->string('tottagbulanberjalanawal')->nullable();
            $table->string('tottagmenunggakawal')->nullable();
            $table->string('tottagsdbulaniniawal')->nullable();
            $table->string('user_sipp')->nullable();

            $table->timestamps();

            $table->unique(['batch_id', 'peserta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_batches');
    }
};
