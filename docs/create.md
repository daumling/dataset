---
title: Creating a Dataset
nav_order: 2
---

## Creating a Dataset

it is always possible to create a dataset with an existing structure:
```php
$master = new Dataset($myData);
```
The data is expected to be an array. If an object is used, it is cast to an array.

## Files

The usual way to create a dataset is to use the `File` class. This class handles JSON files, caching of these files, and global as well as file specific options.

Due to the nature of JSON, associative arrays are stored as objects. If an object is loaded, the code checks the object's properties for being arrays or objects. If all property values are non-scalar, the object is loaded as an associative array, mimicking indexed storage of objects. If at least one property value is scalar, however, the entire object is loaded as a single entry. If you save such an object, it will be saved as a single-element array, producing slightly different data on disk than before.

### Options

The File class suports options, which can either be set globally or passed in as part of the File's constructor call. Here is the default layout of these options:
```php
[
    'path' => '/data/*.json',
    'autoflush' => false,
    'autodelete' => false,
    'cache' => true,
    'flags' => JSON_PRETTY_PRINT // JSON flags for saving	
];
```
Actually the `path` option is the full path of your project's root folder plus `/data/*.json`. You should always set this option to point to your file set before working with Datasets.

  * `path`: The path setting is a placeholder for the path submitted to the File constructor. That constructor takes just the name of the file. The constructor replaces the asterisk in the `path` option with whatever you submit as file name; subfolders are OK as well, and will be created during saving if they do not exist.
  * `autoflush`: If this setting is `true`, any modifications to the dataset will be written to the file immediately.
  * `autodelete`: If `true`, writing an empty dataset causes the file to be deleted.
  * `cache`: If `true`, the File object will be cached, thus reducing disk I/O.
  * `flags`: These flags will be supplied to the `json_encode()` function when the data is saved to disk.

### Caching

To take advantage of file caching, use this static method to access a JSON file:
```php
static function get(string $subpath, array $options = []) : File;
```
Example:
```php
use \Daumling\Dataset\File as File;
$master = File::get('persons/addresses');
```
This will open the JSON file at `{your_root-folder}/data/persons/addresses.json` if it exists; if it does not, the file is initialized with an empty dataset.

```php
static function getPath(string $subpath, array $options = []) : string;
```
This static File function returns the real path of the file to open, using the `path` setting in the supplied or default options.

```php
static function clearCache(string $subpath = '', array $options = []) : void;
``` 
Clear the cache. The path is either empty, which clears the entire cache, or it is a subpath; in the latter case, all cache entries whose path starts with the given path are cleared.

### Listing

To see which JSON files are available, use this method:
```php
static function list(string $subpath, array $options = []) : array;
```
This function returns a list of all files that match the `path` setting in the supplied or default options for the given subpath. Only the parts of the file name that match the pattern are returned, so they can be used to open JSON files.

### What Else?

The File class has a few utility methods:
```php
function exists() : bool;
```
Does the file exist on disk?
```php
function saveAs(string $subpath) : void;
```
Changes the path for the file and saves it under that name.
