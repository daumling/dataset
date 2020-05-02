<?php

namespace Daumling\Dataset;

require_once 'Dataset.php';
require_once 'Exception.php';

/**
 * A DataSubset is a subset of a dataset. It grabs a field from the
 * parent dataset and fills itself with the contents of these fields.
 */
class DataSubset extends Dataset {

    /**
     * Create a data subset.
     * 
     * @param object|array $data the data
     * @param string $field the field to use
     * @param Dataset $parent the parent dataset
     */
    protected function __construct($data, string $field, Dataset $parent) {
        $this->parent = $parent;
        $this->modified = false;
        $this->index = $field;
        $this->data = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[$field]))
                $this->data[$key] = &$value[$field];
            else if (!is_array($value) && isset($value->$field))
                $this->data[$key] = &$value->$field;
        }
    }

    /**
     * Inserting into a sub-field does not make much sense, because
     * there won't be any parent field to insert into.
     * @param array|object $data
     * @param string $key
     * @return Dataset myself
     * @throws Exception
     */
    public function insert($data, ?string $key = null) : Dataset {
        throw new Exception("Cannot insert into a select() dataset");
        return $this;
    }
}
