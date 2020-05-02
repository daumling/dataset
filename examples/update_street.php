<?php

/**
 * Update the street address of a person.
 */
include '../src/Dataset.php';
use \Daumling\Dataset\File as DB;

DB::setOptions([
    'path' => __DIR__.'/data/*.json',
    'autoflush' => false // do not want to overwrite the dataset
]);

$person = DB::get('addresses')
    ->where('first_name', '=', 'Skip')
    ->where('last_name', '=', 'Dulson');
if (!count($person))
    echo ("Skip Dulson not found\n");
else {
    $person->select('address')->update(['street' => '1234 Wonder Drive']);
    echo json_encode($person->fetch(), JSON_PRETTY_PRINT);
}
