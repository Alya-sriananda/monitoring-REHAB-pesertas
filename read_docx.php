<?php

$xml = simplexml_load_file('excel/temp_docx/word/document.xml');
$xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
$texts = $xml->xpath('//w:t');
$out = [];
foreach ($texts as $t) {
    $out[] = (string) $t;
}
file_put_contents('excel/extracted_text.txt', implode("\n", $out));
echo "Done\n";
