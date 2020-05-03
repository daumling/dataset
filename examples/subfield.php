<?php

/**
 * Example: find residents of California older than 65.
 * The state is part of a sub-field "address".
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
// Get all records for people with an age > 65
$set = $table->where('age', '>', 65);
echo count($set)." records with age > 65 found\n";
// Use dotted notation to check a sub-field
$california = $set->where('address.state', '=', 'California');

// A different way to write this
$california = $table->where('age', '>', 65)->where('address.state', '=', 'California');

echo count($california)." California residents found\n";

foreach ($california->fetch() as $record)
    echo json_encode($record, JSON_PRETTY_PRINT)."\n";
