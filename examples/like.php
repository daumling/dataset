<?php

/**
 * RegExp support: Find all records with street address numbers
 * of five or more digits
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
$addresses = $table->where('address.street', 'like', '/^\d{5,} /');

echo count($addresses). " records found\n";

foreach ($addresses->fetch() as $record)
    echo json_encode($record, JSON_PRETTY_PRINT)."\n";
