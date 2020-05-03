## Searching

Searching a table always returns a new Dataset with the matching subset of the original dataset. You can use the usual comparison operators to search. Search via regular expressions is supported with the `like` operator, and it is possible to check for the existence of a data field by comparing it to `null`.

A field name can be dotted to address sub-fields. If, for example, the search should check the field `zip` of an `address` field, use the notation `address.zip`.

Here is an example for such a data record:

```json
{
    "name" : "John Smith",
    "age": 44,
    "address" : {
        "street" : "1234 Wonder St",
        "zip" : 94016,
        "city" : "San Jose",
        "state" : "CA"
    }
}
```
There are two search functions:

```php
function where (string $field, string $op, string $value) : Dataset;
function orWhere (string $field, string $op, string $value) : Dataset;
```
Both functions return a new Dataset containing the search result, so these calls can be chained.

To access the result of a search, use the dataset's `fetch()` method.

The `$op` parameter is the operator to use, which can be one of `=` (or `==`), `!=`, `<`, `>`, `<=`, `>=`, or `like`, and the `$value` field is the value to compare against.

The `like` operator expectes the value to be a regular expression string. Here is an example of finding all records whose city starts with "San ":

```php
$set = $table->where('address.city' 'like', '/^San /');
```
Here is an example of a chained call, finding all NY residents with an age > 65:

```php
$set = $table->where('address.state' '=', 'NY')->orWhere('age' '>', 65);
```

The `orWhere()` method combines its search with the search result of the parent dataset. This call, for example, finds all residents of NY or TX:

```php
$set = $table->where('address.state' '=', 'NY')->orWhere('address.state' '=', 'TX');
```

## Selecting

Often, it is more interesting to see the field values instead of the entire record. Use the `select()` function to select a data field (again, dots are permitted). If you, for example, juzst want a list of Zip codes, use this call:
```php
$set = $table->select('address.zip');
```
A subsequent call to `fetch()` returns an array of Zip codes.
