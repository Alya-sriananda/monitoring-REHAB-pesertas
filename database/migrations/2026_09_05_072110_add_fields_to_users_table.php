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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_petugas', 50)->unique()->after('id');
            $table->string('role', 50)->default('petugas')->after('password');
            $table->boolean('aktif')->default(true)->after('role');
            $table->boolean('must_change_password')->default(true)->after('aktif');
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');

            // make email nullable
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_petugas',
                'role',
                'aktif',
                'must_change_password',
                'password_changed_at',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
