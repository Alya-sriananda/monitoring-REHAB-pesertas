<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Daerah;
use App\Models\Peserta;
use App\Models\PesertaBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;

class BatchImportService
{
    /**
     * Parse the Excel file and return a preview summary.
     * Does NOT write to the database (except temporary file storage).
     */
    public function preview(string $filePath): array
    {
        $hash = md5_file(Storage::path($filePath));

        // Check if exact file has been imported
        $existingBatch = Batch::where('file_hash', $hash)->where('status_proses', 'selesai')->first();
        if ($existingBatch) {
            return [
                'status' => 'rejected',
                'message' => 'File yang sama sudah pernah diimport pada '.$existingBatch->created_at->format('d-m-Y H:i'),
                'file_hash' => $hash,
            ];
        }

        $reader = SimpleExcelReader::create(Storage::path($filePath));

        $stats = [
            'status' => 'preview',
            'file_hash' => $hash,
            'jumlah_row_asli' => 0,
            'jumlah_row_valid' => 0,
            'jumlah_duplicate' => 0,
            'jumlah_conflict' => 0,
            'jumlah_invalid' => 0,
            'jumlah_peserta_baru' => 0,
            'jumlah_peserta_diperbarui' => 0,
            'jumlah_peserta_unchanged' => 0,
            'import_errors' => [],
        ];

        $seenNokas = [];
        $existingNokas = Peserta::pluck('noka')->toArray();
        $existingNokas = array_flip($existingNokas); // For fast O(1) lookup

        $reader->getRows()->each(function (array $rowProperties) use (&$stats, &$seenNokas, $existingNokas) {
            $stats['jumlah_row_asli']++;

            $row = $this->normalizeRow($rowProperties);

            // Validation: Must have at least Noka
            $noka = $row['noka'] ?? null;
            if (! $noka) {
                $stats['jumlah_invalid']++;
                $stats['import_errors'][] = [
                    'row_index' => $stats['jumlah_row_asli'],
                    'reason' => 'Noka tidak ditemukan atau kosong',
                    'data' => $row,
                ];

                return; // skip to next
            }

            // Check Intra-file duplicates
            if (isset($seenNokas[$noka])) {
                $prevRow = $seenNokas[$noka];
                // Compare important fields to detect conflict vs duplicate
                if ($this->isConflict($prevRow, $row)) {
                    $stats['jumlah_conflict']++;
                    $stats['import_errors'][] = [
                        'row_index' => $stats['jumlah_row_asli'],
                        'reason' => 'Konflik dengan data sebelumnya di file yang sama untuk Noka '.$noka,
                        'data' => $row,
                    ];
                } else {
                    $stats['jumlah_duplicate']++;
                }

                return; // skip to next
            }

            // Add to seen for subsequent row checks
            $seenNokas[$noka] = $row;

            // It's a valid row
            $stats['jumlah_row_valid']++;

            if (isset($existingNokas[$noka])) {
                // Determine if updated or unchanged
                // For preview, we assume it's updated as we don't load the entire DB model here
                $stats['jumlah_peserta_diperbarui']++;
            } else {
                $stats['jumlah_peserta_baru']++;
            }
        });

        return $stats;
    }

    /**
     * Execute the import using the file.
     * Uses DB Transaction.
     */
    public function import(string $filePath, array $metadata): Batch
    {
        $hash = md5_file(Storage::path($filePath));

        $existingBatch = Batch::where('file_hash', $hash)->where('status_proses', 'selesai')->first();
        if ($existingBatch) {
            throw new \Exception('File yang sama sudah pernah diimport.');
        }

        return DB::transaction(function () use ($filePath, $metadata, $hash) {
            $batch = Batch::create([
                'tanggal_data' => $metadata['tanggal_data'],
                'nama_file' => basename($metadata['nama_file']),
                'jumlah_data' => 0, // will update later
                'imported_by' => auth()->id(),
                'file_hash' => $hash,
                'status_proses' => 'processing',
            ]);

            $reader = SimpleExcelReader::create(Storage::path($filePath));

            $seenNokas = [];
            $daerahCache = [];
            foreach (Daerah::all() as $daerah) {
                $daerahCache[self::normalizeDaerahName($daerah->nama)] = $daerah->id;
            }

            $stats = [
                'jumlah_row_asli' => 0,
                'jumlah_row_valid' => 0,
                'jumlah_duplicate' => 0,
                'jumlah_conflict' => 0,
                'jumlah_invalid' => 0,
                'jumlah_peserta_baru' => 0,
                'jumlah_peserta_diperbarui' => 0,
                'import_errors' => [],
            ];

            $seenRows = [];
            $seenNokas = [];

            $reader->getRows()->each(function (array $rowProperties) use (&$stats, &$seenRows, &$seenNokas, $daerahCache, $batch) {
                $stats['jumlah_row_asli']++;

                $row = $this->normalizeRow($rowProperties);
                $noka = $row['noka'] ?? null;
                $namaentitas = $row['namaentitas'] ?? null;

                if (! $noka) {
                    $stats['jumlah_invalid']++;
                    $stats['import_errors'][] = [
                        'row' => $stats['jumlah_row_asli'],
                        'noka' => 'N/A',
                        'namaentitas' => $namaentitas,
                        'kategori' => 'Invalid',
                        'reason' => 'Invalid Noka (Kosong)',
                    ];

                    return;
                }

                $rowHash = md5(json_encode($row));
                if (isset($seenRows[$rowHash])) {
                    $stats['jumlah_duplicate']++;
                    $stats['import_errors'][] = [
                        'row' => $stats['jumlah_row_asli'],
                        'noka' => $noka,
                        'namaentitas' => $namaentitas,
                        'kategori' => 'Duplicate',
                        'reason' => 'Baris identik dengan baris sebelumnya',
                    ];

                    return;
                }
                $seenRows[$rowHash] = true;

                // Check conflict
                if (isset($seenNokas[$noka])) {
                    if ($this->isConflict($seenNokas[$noka], $row)) {
                        $stats['jumlah_conflict']++;
                        $stats['import_errors'][] = [
                            'row' => $stats['jumlah_row_asli'],
                            'noka' => $noka,
                            'namaentitas' => $namaentitas,
                            'kategori' => 'Conflict',
                            'reason' => 'Konflik dengan data sebelumnya di file yang sama untuk Noka '.$noka,
                        ];

                        return; // skip conflict row
                    }
                }
                $seenNokas[$noka] = $row;

                $stats['jumlah_row_valid']++;

                // Validate Daerah
                $daerahId = null;
                if (! empty($row['nmdati2'])) {
                    $daerahName = self::normalizeDaerahName($row['nmdati2']);
                    $daerahId = $daerahCache[$daerahName] ?? null;
                    if (! $daerahId) {
                        $stats['import_errors'][] = [
                            'row' => $stats['jumlah_row_asli'],
                            'noka' => $noka,
                            'namaentitas' => $namaentitas,
                            'kategori' => 'Warning',
                            'reason' => "Daerah {$row['nmdati2']} tidak ditemukan di master.",
                        ];
                    }
                }

                // Upsert Peserta (Insert or Update)
                $peserta = Peserta::firstOrNew(['noka' => $noka]);
                $isNew = ! $peserta->exists;

                $peserta->fill([
                    'nama' => $row['namaentitas'] ?? $peserta->nama, // Use existing if excel is blank
                    'no_hp' => $row['nohp'] ?? $peserta->no_hp,
                    'email' => $row['email'] ?? $peserta->email,
                    'alamat' => $row['alamat'] ?? $peserta->alamat,
                    'status_aktif' => $row['statusaktif'] ?? $peserta->status_aktif,
                    'nopendaftar' => $row['nopendaftar'] ?? $peserta->nopendaftar,
                    'nopenghubung' => $row['nopenghubung'] ?? $peserta->nopenghubung,
                ]);

                if ($daerahId) {
                    $peserta->daerah_id = $daerahId;
                }

                $peserta->save();

                if ($isNew) {
                    $stats['jumlah_peserta_baru']++;
                } else {
                    $stats['jumlah_peserta_diperbarui']++;
                }

                // Create or Update PesertaBatch Snapshot
                PesertaBatch::updateOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'peserta_id' => $peserta->id,
                    ],
                    [
                        'data_source' => 'excel',
                        'statusaktif' => $row['statusaktif'] ?? null,
                        'total_peserta' => $row['total_peserta'] ?? null,
                        'zero' => $row['zero'] ?? null,
                        'alamat' => $row['alamat'] ?? null,
                        'bulan_menunggak' => $row['bulan_menunggak'] ?? null,
                        'cabang_kd' => $row['cabang_kd'] ?? null,
                        'divre_kd' => $row['divre_kd'] ?? null,
                        'email' => $row['email'] ?? null,
                        'endcicilan' => $row['endcicilan'] ?? null,
                        'idcicilan' => $row['idcicilan'] ?? null,
                        'index_data' => $row['index_data'] ?? null,
                        'jmlbulancicilawal' => $row['jmlbulancicilawal'] ?? null,
                        'jmlbulanmenunggakawal' => $row['jmlbulanmenunggakawal'] ?? null,
                        'kanal_pendaftaran' => $row['kanal_pendaftaran'] ?? null,
                        'kantor_cabang' => $row['kantor_cabang'] ?? null,
                        'kddati2' => $row['kddati2'] ?? null,
                        'kddesa' => $row['kddesa'] ?? null,
                        'kdkec' => $row['kdkec'] ?? null,
                        'kelas' => $row['kelas'] ?? null,
                        'kelas_group' => $row['kelas_group'] ?? null,
                        'namaentitas' => $row['namaentitas'] ?? null,
                        'nmdati2' => $row['nmdati2'] ?? null,
                        'nmdesa' => $row['nmdesa'] ?? null,
                        'nmkc' => $row['nmkc'] ?? null,
                        'nmkec' => $row['nmkec'] ?? null,
                        'noentitas' => $row['noka'] ?? null, // using normalized NOKA
                        'nohp' => $row['nohp'] ?? null,
                        'nopendaftar' => $row['nopendaftar'] ?? null,
                        'nopenghubung' => $row['nopenghubung'] ?? null,
                        'startcicilan' => $row['startcicilan'] ?? null,
                        'tanggalupdatedata' => $row['tanggalupdatedata'] ?? null,
                        'tglcicilan' => $row['tglcicilan'] ?? null,
                        'tottagbulanberjalanawal' => $row['tottagbulanberjalanawal'] ?? null,
                        'tottagmenunggakawal' => $row['tottagmenunggakawal'] ?? null,
                        'tottagsdbulaniniawal' => $row['tottagsdbulaniniawal'] ?? null,
                        'user_sipp' => $row['user_sipp'] ?? null,
                    ]);
            });

            // Update Batch
            $batch->update([
                'jumlah_data' => $stats['jumlah_row_valid'],
                'jumlah_row_asli' => $stats['jumlah_row_asli'],
                'jumlah_row_valid' => $stats['jumlah_row_valid'],
                'jumlah_duplicate' => $stats['jumlah_duplicate'],
                'jumlah_conflict' => $stats['jumlah_conflict'],
                'jumlah_invalid' => $stats['jumlah_invalid'],
                'jumlah_peserta_baru' => $stats['jumlah_peserta_baru'],
                'jumlah_peserta_diperbarui' => $stats['jumlah_peserta_diperbarui'],
                'status_proses' => 'selesai',
                'import_errors' => empty($stats['import_errors']) ? null : json_encode($stats['import_errors']),
            ]);

            return $batch;
        });
    }

    /**
     * Normalize the row data according to business rules.
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            // Stop processing if we hit headers after "User Sipp"
            // We do this by mapping known headers.
            // SimpleExcel handles header keys by lowercasing and replacing spaces, but we can't rely on it perfectly.
            $cleanKey = strtolower(trim(str_replace([' ', '_', '(', ')'], '', $key)));
            $cleanValue = is_string($value) ? trim($value) : $value;

            // Normalize empty strings or '-' to null
            if ($cleanValue === '' || $cleanValue === '-') {
                $cleanValue = null;
            }

            $normalized[$cleanKey] = $cleanValue;
        }

        // Map expected Excel headers to standard names we use
        return [
            'statusaktif' => $normalized['statusaktif'] ?? null,
            'total_peserta' => $normalized['totalpeserta'] ?? null,
            'zero' => $normalized['zero'] ?? null,
            'alamat' => $normalized['alamat'] ?? null,
            'bulan_menunggak' => $normalized['bulanmenunggak'] ?? null,
            'cabang_kd' => $normalized['cabangkd'] ?? null,
            'divre_kd' => $normalized['divrekd'] ?? null,
            'email' => $normalized['email'] ?? null,
            'endcicilan' => $normalized['endcicilan'] ?? null,
            'idcicilan' => $normalized['idcicilan'] ?? null,
            'index_data' => $normalized['indexdata'] ?? null,
            'jmlbulancicilawal' => $normalized['jmlbulancicilawal'] ?? null,
            'jmlbulanmenunggakawal' => $normalized['jmlbulanmenunggakawal'] ?? null,
            'kanal_pendaftaran' => $normalized['kanalpendaftaran'] ?? null,
            'kantor_cabang' => $normalized['kantorcabang'] ?? null,
            'kddati2' => $normalized['kddati2'] ?? null,
            'kddesa' => $normalized['kddesa'] ?? null,
            'kdkec' => $normalized['kdkec'] ?? null,
            'kelas' => $normalized['kelas'] ?? null,
            'kelas_group' => $normalized['kelasgroup'] ?? null,
            'namaentitas' => $normalized['namaentitas'] ?? null,
            'nmdati2' => $normalized['nmdati2'] ?? null,
            'nmdesa' => $normalized['nmdesa'] ?? null,
            'nmkc' => $normalized['nmkc'] ?? null,
            'nmkec' => $normalized['nmkec'] ?? null,
            'noka' => $normalized['noentitas'] ?? null, // NOKA
            'nohp' => $this->normalizePhone($normalized['nohp'] ?? null),
            'nopendaftar' => $normalized['nopendaftar'] ?? null,
            'nopenghubung' => $normalized['nopenghubung'] ?? null,
            'startcicilan' => $normalized['startcicilan'] ?? null,
            'tanggalupdatedata' => $normalized['tanggalupdatedata'] ?? null,
            'tglcicilan' => $normalized['tglcicilan'] ?? null,
            'tottagbulanberjalanawal' => $this->normalizeNumber($normalized['tottagbulanberjalanawal'] ?? null),
            'tottagmenunggakawal' => $this->normalizeNumber($normalized['tottagmenunggakawal'] ?? null),
            'tottagsdbulaniniawal' => $this->normalizeNumber($normalized['tottagsdbulaniniawal'] ?? null),
            'user_sipp' => $normalized['usersipp'] ?? null,
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        // Keep leading zero but remove weird characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        return $phone === '' ? null : $phone;
    }

    private function normalizeNumber($value)
    {
        if ($value === null) {
            return null;
        }
        $num = preg_replace('/[^0-9]/', '', $value);

        return $num === '' ? null : (int) $num;
    }

    public static function normalizeDaerahName(string $name): string
    {
        $name = strtolower($name);
        $name = str_replace(['.', ','], '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));

        return $name;
    }

    private function isConflict(array $row1, array $row2): string|false
    {
        // Define important fields that shouldn't change for the same NOKA in one file
        $importantFields = [
            'namaentitas', 'idcicilan', 'tottagsdbulaniniawal',
        ];

        foreach ($importantFields as $field) {
            $val1 = isset($row1[$field]) ? trim((string) $row1[$field]) : '';
            $val2 = isset($row2[$field]) ? trim((string) $row2[$field]) : '';

            if ($val1 !== $val2) {
                return $field;
            }
        }

        return false;
    }
}
