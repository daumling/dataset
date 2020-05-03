<?php

/**
 * Delete data: Delete the gender from all records
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
$table->delete('gender');

// check the deletion
$set = $table->where('gender', '!=', '');

echo count($set). " records found\n";

// display
foreach ($set->fetch() as $record)
    echo json_encode($record, JSON_PRETTY_PRINT)."\n";
