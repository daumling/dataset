<?php

/**
 * Sort example: Sort by last name, and display the first 5 entries.
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
$set = $table
    ->sort('last_name')
    ->limit(5);

if (empty($set))
    echo "No data\n";
// display
else foreach ($set->fetch() as $record)
    echo json_encode($record, JSON_PRETTY_PRINT)."\n";
