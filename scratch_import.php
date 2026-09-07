<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BatchImportService;
use App\Models\Daerah;
use App\Models\Peserta;

// Get actual count before
$pesertaBefore = Peserta::count();

$service = app(BatchImportService::class);
$file = 'DATAREHABAGST2026.xlsx';
// fake the file path to storage
copy(__DIR__.'/'.$file, storage_path('app/'.$file));

$stats = $service->preview($file);
echo "Preview Stats:\n";
print_r($stats);

// We won't run actual import in script because of auth()->id() dependency which is null in CLI unless we login.
// We will mock auth
Auth::loginUsingId(1);
$batch = $service->import($file, ['tanggal_data' => '2026-08-01', 'nama_file' => 'DATAREHABAGST2026.xlsx']);

echo "\nImported Batch ID: {$batch->id}\n";
echo "Total Peserta now: " . Peserta::count() . "\n";
echo "Peserta without Daerah: " . Peserta::whereNull('daerah_id')->count() . "\n";
echo "Peserta with Daerah: " . Peserta::whereNotNull('daerah_id')->count() . "\n";

// Sample checks
$nokas = ['0002752719546', '0003527195591', '0003597558693', '0002222060433', '0000019620281'];
foreach ($nokas as $noka) {
    $p = Peserta::with('daerah')->where('noka', $noka)->first();
    if ($p) {
        echo "NOKA: $noka | Nmdati2: (from excel) | daerah_id: {$p->daerah_id} | nama daerah: " . ($p->daerah ? $p->daerah->nama : 'NULL') . "\n";
    } else {
        echo "NOKA: $noka NOT FOUND\n";
    }
}
