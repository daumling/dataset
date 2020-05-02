<?php

/**
 * Example: List all unique Zip codes found in the address database.
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$zips = DB::get('addresses')->select('address.zip')->fetch();
$zips = array_unique($zips);

echo count($zips)." Zip codes found\n";
echo implode(' ', $zips)."\n";
