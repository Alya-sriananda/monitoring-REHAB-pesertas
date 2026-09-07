<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $tempExcelPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'petugas',
            'aktif' => true,
            'must_change_password' => false,
        ]);

        Storage::fake('local');

        // Create a dummy Excel/CSV file for testing
        $this->tempExcelPath = storage_path('app/temp_test.csv');
        $writer = SimpleExcelWriter::create($this->tempExcelPath);
        $writer->addRows([
            [
                'Statusaktif' => 'AKTIF',
                'Noentitas' => '1234567890123', // NOKA
                'Namaentitas' => 'JOHN DOE',
                'Nohp' => '081234567890',
                'Nmdati2' => 'SOLOK',
                'User Sipp' => 'admin_sipp',
            ],
            [
                'Statusaktif' => 'AKTIF',
                'Noentitas' => '1234567890123', // NOKA Duplicate exact
                'Namaentitas' => 'JOHN DOE',
                'Nohp' => '081234567890',
                'Nmdati2' => 'SOLOK',
                'User Sipp' => 'admin_sipp',
            ],
            [
                'Statusaktif' => 'AKTIF',
                'Noentitas' => '1234567890123', // NOKA Conflict
                'Namaentitas' => 'JOHN DOE DIFFERENT',
                'Nohp' => '081234567890',
                'Nmdati2' => 'SOLOK',
                'User Sipp' => 'admin_sipp',
            ],
            [
                'Statusaktif' => 'AKTIF',
                'Noentitas' => '9876543210987', // New NOKA
                'Namaentitas' => 'JANE DOE',
                'Nohp' => '081234567899',
                'Nmdati2' => 'PADANG',
                'User Sipp' => 'admin_sipp',
            ],
            [
                'Statusaktif' => 'AKTIF',
                'Noentitas' => '', // Invalid (No NOKA)
                'Namaentitas' => 'INVALID DOE',
            ],
        ]);
        $writer->close();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempExcelPath)) {
            unlink($this->tempExcelPath);
        }
        parent::tearDown();
    }

    public function test_import_preview_analyzes_file_correctly_without_db_changes()
    {
        Daerah::factory()->create(['nama' => 'SOLOK']);

        $file = new UploadedFile(
            $this->tempExcelPath,
            'test_import.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($this->admin)->postJson(route('batches.preview'), [
            'file' => $file,
            'tanggal_data' => '2026-09-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('stats.jumlah_row_asli', 5)
            ->assertJsonPath('stats.jumlah_invalid', 1) // Missing NOKA
            ->assertJsonPath('stats.jumlah_duplicate', 1) // Exact match
            ->assertJsonPath('stats.jumlah_conflict', 1) // Same NOKA, diff name
            ->assertJsonPath('stats.jumlah_row_valid', 2); // 1st John Doe, 1st Jane Doe

        // Assert DB hasn't changed
        $this->assertDatabaseCount('batches', 0);
        $this->assertDatabaseCount('pesertas', 0);
    }

    public function test_import_commit_saves_data_and_maps_daerah()
    {
        $solok = Daerah::factory()->create(['nama' => 'SOLOK']);
        Daerah::factory()->create(['nama' => 'PADANG']);

        $file = new UploadedFile(
            $this->tempExcelPath,
            'test_import.csv',
            'text/csv',
            null,
            true
        );

        $previewResponse = $this->actingAs($this->admin)->postJson(route('batches.preview'), [
            'file' => $file,
            'tanggal_data' => '2026-09-01',
        ]);

        $filePath = $previewResponse->json('file_path');

        $importResponse = $this->actingAs($this->admin)->post(route('batches.store'), [
            'file_path' => $filePath,
            'original_name' => 'test_import.csv',
            'tanggal_data' => '2026-09-01',
        ]);

        $importResponse->assertRedirect();

        // Assert Batch created
        $this->assertDatabaseCount('batches', 1);
        $batch = Batch::first();
        $this->assertEquals(2, $batch->jumlah_row_valid);
        $this->assertEquals(1, $batch->jumlah_duplicate);
        $this->assertEquals(1, $batch->jumlah_conflict);
        $this->assertEquals(1, $batch->jumlah_invalid);
        $this->assertEquals('selesai', $batch->status_proses);

        // Assert Pesertas created
        $this->assertDatabaseCount('pesertas', 2);

        $john = Peserta::where('noka', '1234567890123')->first();
        $this->assertNotNull($john);
        $this->assertEquals('JOHN DOE', $john->nama);
        $this->assertEquals($solok->id, $john->daerah_id);

        // Assert Pivot created
        $this->assertDatabaseCount('peserta_batches', 2);
    }

    public function test_duplicate_file_is_rejected()
    {
        $file = new UploadedFile(
            $this->tempExcelPath,
            'test_import.csv',
            'text/csv',
            null,
            true
        );

        // First import
        $preview1 = $this->actingAs($this->admin)->postJson(route('batches.preview'), [
            'file' => clone $file,
            'tanggal_data' => '2026-09-01',
        ]);
        $this->actingAs($this->admin)->post(route('batches.store'), [
            'file_path' => $preview1->json('file_path'),
            'original_name' => 'test_import.csv',
            'tanggal_data' => '2026-09-01',
        ]);

        // Second import preview should reject
        $preview2 = $this->actingAs($this->admin)->postJson(route('batches.preview'), [
            'file' => clone $file,
            'tanggal_data' => '2026-09-01',
        ]);

        $preview2->assertStatus(422)
            ->assertJsonPath('error', fn ($error) => str_contains($error, 'File yang sama sudah pernah diimport'));
    }
}
