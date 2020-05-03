---
title: Modifying Data
nav_order: 4
---

## Modifying Data

There are numerous calls available to modify a dataset. All calls set the modified flag of the affected dataset and its parents. If the master dataset is a file or another storage backend, and it was opened with the option `autoflush` to `true`, altering data is committed to storage immediately, which may cause exceptions to be thrown because of write errors. See also `flush()` below.

### Data By Reference

All dataset data is maintained and returned by reference. It is entirely possible to modify a record or a dataset by changing the fields of the returned data. This change affects the entire chain of parents, and the master dataset as well. To make the datasets aware of the change, call its `setModified()` method:

```php
function setModified(bool $modified = true) : void;
```
A call to this method may cause the master dataset to be written to storage if its `autoflush` option is set to `true`, possibly causing an exception to be thrown in case of write errors.

### Setting it all

```php
function set($data, bool $modified = true) : Dataset;
```
Replace the entire dataset with new data. If the data is an array, the data is set without any modifications. If the data is an object, the data is treated as a single record with the key "0".

Optionally, the modifed flag can be set or cleared during this operation. This call affects the current dataset only; any parent or master datasets are not affects, so better use this method on a master dataset.

### Adding Data

```php
function insert($data, $key = null) : Dataset;
```

Add data to the dataset. The data can either be an array or an object. If a key is supplied, that key is used to add the data, which may lead to a silent overwrite of existing data. If no key is supplied, the data is appended to the dataset. Returns itself so calls can be chained.

Please note that insertion is not possible for a dataset which is the result of a call to `select()`. An attempt to insert data into such a dataset throws an exception.

```php
function insertMany(array $data) : Dataset;
```
Add multiple records to the dataset. The array is an array of records to be added. The array keys are not used, so it you want to add data with an idex, use the `insert()` method instead. Returns itself so calls can be chained.

### Changing Fields

```php
function update($data) : Dataset;
```
Update a dataset. The data is either an object or an array whose data is merged into each record of this dataset. The update operation also affects all parent datasets, causing the file to be updated at the end if autoflush is enabled. Note that nothing happens if the dataset is empty. Also, scalar dataset values are not updated.

### Sorting

```php
function sort(string $field = '', string $mode = 'asc') : Dataset;
```
Sort the dataset. Note that this changes this dataset only, not any parent or other datasets. If you want to sort by index, use '*' as the field name. Note that objects and arrays are not compared. When a scalar value is compared to an object or array the scalar value wins, and two objects or arrays are always assumed to be equal. The elements are compared using PHP's `<=>` operator.

Sorting is either ascending (`asc`) or descending (`desc`).

The key may contain dots to address a sub-field. The return value is the dataset itself so calls can be chained.

### Deletions

```php
function delete(string $field = null) : Dataset;
```
Delete a field or the entire dataset. 

If no field name is supplied, after this call, the dataset is empty, and its records are removed from all parents.

If the dataset is the result of a `select()` call, only the field that the dataset was selected over is removed from the parents. If you, for example, have records with an `age` field, this call removes that field from the master dataset:

```php
$set->select('age')->delete();
```
If a field is supplied, only that field is removed. The above call could also be written in a simpler way as
```php
$set->delete('age');
```
### Re-Indexing

Sometimes, a re-index may be needed, especially for large datasets. Imagine an address database that you would like to reindex by the social security number.
```php
function reindex(string $field = null, bool $ignoreDuplicates = false) : Dataset;
function reindex_callback(callable $cb, bool $ignoreDuplicates = false) : Dataset;
```
These two methods allow for the re-indexing, which sets the key of each record to the value of the given field. You can choose to ignore duplicates, which causes the records to be overwritten is the same index appears twice. The default is to throw an exception for duplicate index values.

The second method ifs for more complex reindex operations. The callback has the following signature:
```php
function callback($string oldKey, $data, Dataset $self);
```
The function is called with the current key, the record to reindex, and the Dataset instance for that record. It returns the new index value.

Note that reindexing only changes the current dataset, so it should rather be used with a master dataset.
