<?php

/**
 * Simple example with OR: find all residents
 * of New York and Texas
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
$set = $table
    ->where('address.state', '=', 'New York')
    ->orWhere('address.state', '=', 'Texas');

echo count($set)." residents of NY or TX found\n";

if (empty($set))
    echo "No data\n";
// display
else foreach ($set->fetch() as $record)
    echo json_encode($record, JSON_PRETTY_PRINT)."\n";
