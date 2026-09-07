<?php
require 'vendor/autoload.php';
use Spatie\SimpleExcel\SimpleExcelReader;
$reader = SimpleExcelReader::create('DATAREHABAGST2026.xlsx');
$daerahs = [];
$reader->getRows()->each(function($row) use (&$daerahs) {
    // lowercase all keys to find nmdati2
    $normalizedRow = [];
    foreach ($row as $k => $v) {
        $normalizedRow[strtolower(trim($k))] = $v;
    }
    
    $d = $normalizedRow['nmdati2'] ?? null;
    if ($d !== null) {
        $daerahs[$d] = ($daerahs[$d] ?? 0) + 1;
    }
});
print_r($daerahs);
