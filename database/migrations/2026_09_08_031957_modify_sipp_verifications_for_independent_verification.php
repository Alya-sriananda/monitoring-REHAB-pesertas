<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add peserta_id as nullable first
        Schema::table('sipp_verifications', function (Blueprint $table) {
            $table->foreignId('peserta_id')->nullable()->after('rehab_case_id')->constrained('pesertas')->cascadeOnDelete();
        });

        // 2. Backfill peserta_id from existing rehab_cases (Database agnostic for SQLite testing)
        $verifications = DB::table('sipp_verifications')->whereNotNull('rehab_case_id')->get();
        foreach ($verifications as $verification) {
            $case = DB::table('rehab_cases')->where('id', $verification->rehab_case_id)->first();
            if ($case) {
                DB::table('sipp_verifications')
                    ->where('id', $verification->id)
                    ->update(['peserta_id' => $case->peserta_id]);
            }
        }

        // 3. Alter columns
        Schema::table('sipp_verifications', function (Blueprint $table) {
            // Make peserta_id NOT NULL
            $table->unsignedBigInteger('peserta_id')->nullable(false)->change();

            // Drop existing foreign key and column config for rehab_case_id
            $table->dropForeign(['rehab_case_id']);
        });

        Schema::table('sipp_verifications', function (Blueprint $table) {
            // Make rehab_case_id nullable and add back foreign key with nullOnDelete
            // We use nullOnDelete so that deleting a RehabCase does not cascade and delete the historical verification.
            $table->unsignedBigInteger('rehab_case_id')->nullable()->change();
            $table->foreign('rehab_case_id')->references('id')->on('rehab_cases')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sipp_verifications', function (Blueprint $table) {
            // Revert rehab_case_id back to NOT NULL and cascadeOnDelete
            $table->dropForeign(['rehab_case_id']);
        });

        // We can't strictly revert rehab_case_id to NOT NULL if there are records with NULL rehab_case_id created in the meantime,
        // but for a strict rollback, we assume we delete or fix them manually.
        DB::statement('DELETE FROM sipp_verifications WHERE rehab_case_id IS NULL');

        Schema::table('sipp_verifications', function (Blueprint $table) {
            $table->unsignedBigInteger('rehab_case_id')->nullable(false)->change();
            $table->foreign('rehab_case_id')->references('id')->on('rehab_cases')->cascadeOnDelete();

            // Drop peserta_id
            $table->dropForeign(['peserta_id']);
            $table->dropColumn('peserta_id');
        });
    }
};
