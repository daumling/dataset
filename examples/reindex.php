<?php

/**
 * Using an index: The address table is reindexed by Zip code (duplicates ignored),
 * then, a new record is added.
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
$table->reindex('address.zip', true);
$table->insert((object) [
    "first_name" => "John",
    "last_name" => "Smith",
    "email" => "smith@adobe.com",
    "gender" => "Male",
    "age" => 45,
    "address" => (object) [
        "street" => "4124 Lemoyne Way",
        "zip" => 94008,
        "city" => "Campbell",
        "state" => "California"
    ]
], 94008);

// This is very fast
$set = $table->where('*', '=', 94008);
echo count($set). " records found\n";
echo json_encode($set->fetch(), JSON_PRETTY_PRINT)."\n";
