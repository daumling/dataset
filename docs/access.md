---
title: Accessing the Data
nav_order: 4
---

# Accessing the Data

There are several ways to access the dataset's data. Note that all data is returned by reference, so altering the data is entirely possible.

```php
function empty() : bool;
``` 
Returns `true` if the dataset is empty.

```php
function count() : int;
``` 
Returns the number of records in the dataset. Also usable as `count($set)`.

```php
function fetch() : array;
``` 
Fetch the entire dataset. The returned array contains all records of the dataset, with the keys being the keys of the master dataset.

```php
function first() : ?object;
``` 
Fetch the first element of the dataset. This is the element with the lowest key value. If the dataset is empty, `null` is returned.

```php
function last() : ?object;
``` 
Fetch the last element of the dataset. This is the element with the highest key value. If the dataset is empty, `null` is returned.

```php
function skip(int $n) : Dataset;
``` 
Skip the first $n records, and return a Dataset with the remaining records.

```php
function limit(int $n) : Dataset;
``` 
Limit the dataset to the first $n records, and return a Dataset with these  records.

```php
function keys() ; array;
``` 
Return all keys of this dataset. The keys are always the same keys as the master dataset.
