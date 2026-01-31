<?php
// Add UTF-8 BOM to CSV file
$csv_file = 'import_sales_user_data.csv';
$content = file_get_contents($csv_file);
$bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
file_put_contents($csv_file, $bom . $content);
echo "UTF-8 BOM added to $csv_file\n";
