# php-datasets

**See the entire documentation at https://daumling.github.io/php-dataset/**!

I was looking for a simple way to manipulate complex PHP data structures. A quick research found a few solutions, which all fell short in some way.

The Dataset object takes an associative array, whose members are either arrays or objects. It provides SQL-like calls to search and manipulate data, and offers JSON files as a storage medium. Other storage solutions can be added easily with subclassing.

This Dataset implementation offers

  * Easy handling of JSON data
  * multiple search possibilities, including regular expressions
  * hiding the differences between PHP arrays and objects
  * multiple storage backends

Each search operation returns a new Dataset containing the results of the search only. The data of these child datasets is always stored by reference, which saves memory and makes updates easy. Of course, accidential updates may go unnoticed, so this concept needs to be used with caution.

Almost all Dataset methods return a Dataset instance, either a new dataset, or the dataset itself, so calls can be chained easily. Datasets containing intermediate results may be saved for later use.

Here is an example. Assuming a JSON database of persons with the following example record:
```json
{
    "name" : "John Smith",
    "age": 44,
    "address" : {
        ...
        "state" : "CA"
    }
}
```
You can easily iterate over, say, retired persons with an age of 65 or more:

```php
foreach ($file->where('age', '>=', 65)->fetch() as $record) ...
```
Or if you only need NY retirees:
```php
foreach ($file->where('age', '>=', 65)->where('address.state' '=', 'NY')->fetch() as $record) ...
```

## Installation

The easiest way to install and use Datasets is `composer`:

  `composer require daumling/php-dataset`

Your PHP file could look like this:

```php
require_once __DIR__.'/vendor/autoload.php';
use \Daumling\Dataset\File as DB;

// set the path
DB::setOptions([
    'path' => __DIR__.'/data/*.json'
]);
// Open data/addresses.json
$file = DB::get('addresses');
```
