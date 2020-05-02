<?php

/**
 * Simple example: Find all females over 65 years
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
// Now get all females
$female = $set->where('gender', '=', 'Female');
echo count($female)." females found\n";

// A different way to write this
$female = $table
    ->where('age', '>', 65)
    ->where('gender', '=', 'Female');

if (empty($female))
    echo "No data\n";
// display
else foreach ($female->fetch() as $record)
    echo json_encode($record, JSON_PRETTY_PRINT)."\n";
