<?php

/**
 * Delete data: Delete the address of all minors < 18,
 * then count all records that still have an address.
 * 
 * This is an extension of deleteAddress.php.
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);

$table = DB::get('addresses');
$count = count($table);
$set = $table->where('age', '<', 18);
$set->select('address')->delete();

echo ($count - count($table)). " records deleted in table\n";

$set = $table->where('address', '=', null);
echo count($set)." records without an address found\n";

foreach ($set->fetch() as $record)
    echo (json_encode($record, JSON_PRETTY_PRINT))."\n";
