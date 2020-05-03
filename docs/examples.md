---
title: Examples
nav_order: 6
---

# Examples

The examples in the `/examples` folder work with sample data, courtesy by [Mockaroo](https://mockaroo.com/). This data is 100 mock person records with first and last name, email, age and gender, plus an address field with street, zip, city, and state fields. The data is located at `data/addresses.json`.

This is a sample data record:
```json
{
    "first_name": "Leela",
    "last_name": "Blackesland",
    "email": "lblackesland0@skyrock.com",
    "gender": "Female",
    "age": 22,
    "address": {
        "street": "03 Swallow Alley",
        "zip": "85743",
        "city": "Tucson",
        "state": "Arizona"
    }
}
```

## simple.php

Find all females older than 65.

## simple_or.php

Find all residents of New York or Texas.

## subfield.php

Demoes a field name with dotted notation to access the `state` field of the `address` subfield.

## subfield_zips.php

Shows how to select a field from a dataset by returning a list of unique Zip codes from the address data.

## sort.php

Sort the dataset by last name.

## like.php

RegExp: Find all records with a street address number of five or more digits.

## deleteField.php

Delete the gender for all address entries.

## deleteAddress.php

Delete using a select: delete the address of all minors with an age < 18.

## exists.php

An extension of `deleteAddress.php`, where the table is checked for missing address fields.

## reindex.php

Re-index the table by Zip code, add a record and look it up.

## list.php

List all files that match the current setting of the "path" option.

