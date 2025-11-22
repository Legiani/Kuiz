<?php
$file = 'out.csv';
$handle = fopen($file, 'r');
if ($handle) {
    // Skip header
    fgetcsv($handle, 1000, ";", '"', "\\");
    
    // Get first row
    $data = fgetcsv($handle, 1000, ";", '"', "\\");
    if ($data) {
        $text = $data[0]; // First question
        echo "Original Hex: " . bin2hex($text) . "\n";
        
        $encodings = ['Windows-1250', 'ISO-8859-2', 'UTF-8'];
        foreach ($encodings as $enc) {
            $converted = @mb_convert_encoding($text, 'UTF-8', $enc);
            echo "From $enc: " . $converted . "\n";
        }
    }
    fclose($handle);
}
?>
