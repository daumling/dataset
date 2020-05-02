<?php

namespace Daumling\Dataset;

/**
 * The Dataset class is the core class with most of the functionality. It can be used
 * as a standalone instance, or together with the File class, which is a subclass of
 * Dataset.
 * 
 * Datasets can handle both arrays and objects, but all Datasets that are returned
 * contain arrays. Array keys are maintained through all chains of instances to make
 * updates of the original dataset possible.
 * 
 * All data is stored by reference to save memory, and to propagate updates across
 * the chain of sub-datasets. Modifying returned data is OK, but you will need to
 * call flush() on the dataset to save the changes to disk.
 */
class Dataset implements \Countable {

    /**
     * @var Dataset
     */
    protected $parent;
    /**
     * @var array
     */
    protected $data;
    /**
     * @var bool
     */
    protected $modified;

    /**
     * Create a dataset.
     * 
     * @param object|array $data the data
     */
    protected function __construct($data = []) {
        $this->parent = null;
        $this->data = (array) $data;
        $this->modified = false;
    }

    /**
     * Replace the entire dataset with either structured data
     * or the contents of a Dataset.
     * @param array|object|Dataset $data the data to set
     * @param bool $modified the modified flag
     * @return Dataset myself
     */
    function set($data, bool $modified = false) : Dataset {
        if ($data instanceof Dataset)
            $data = $data->data;
        $this->data = (array) $data;
        $this->modified = $modified;
        $this->_autoflush();
        return $this;
    }

    /**
     * Re-index the dataset by using the value of a field as index.
     * If you ignore duplicates, duplicates will overwrite each other.
     * Note that this changes the current dataset only, not any parent
     * or other datasets.
     * 
     * @param string $field the field to use as key
     * @param bool $ignoreDuplicates true to ignore duplicates
     * @return Dataset myself
     * @throws Exception on duplicate or missing keys
     */
    function reindex(string $field, bool $ignoreDuplicates = false) : Dataset {
        $data = [];
        foreach ($this->data as $oldKey => &$record) {
            $key = (is_object($record) ? $record->$field : $record[$field]) ?? null;
            if (is_null($key))
                throw new Exception("Record $oldKey does not have a field '$field'");
            if (isset($data[$key]) && !$ignoreDuplicates)
                throw new Exception("Duplicate key $key");
            $data[$key] = &$record;
        }
        $this->data = $data;
        $this->modified = true;
        return $this;
    }

    /**
     * Return the dataset's modified flag.
     * @return bool
     */
    function modified() : bool {
        return $this->modified;
    }

    /**
     * Set the dataset's modified flag. If set to true, it may
     * cause a flush operation if the "autoflush" option was
     * set.
     * @param bool $flag
     */
    function setModified(bool $flag) : void {
        $this->modified = $flag;
        $this->_autoflush();
    }

    /**
     * Execute a search. If the operator is "like", the value is a RegExp string.
     * If you want to address the recrod by its key, use "*" as the field name 
     * and "=" as the operator.
     * 
     * @param string $field - the data field (may contains dots for multiple levels)
     * @param string $op - the operator, one of =, != <, >, <=, >=, like
     * @param bool|int|double|string $value - the value to compare to, must be scalar
     * @return Dataset
     */
    function where(string $field, string $op, $val) : Dataset {
        $set = new Dataset();
        $set->parent = $this;
        $this->_load();
        // Key access?
        if ($field === '*' && $op === '=') {
            if (isset($this->data[$val]))
                $set->data[] = $this->data[$val];
            return $set;
        }
        // If the field is dotted, work on a subset of the field
        // except for the last field, which we need to compare with
        $keys = explode('.', $field);
        $field = array_pop($keys);
        if (empty($keys))
            $search = $this;
        else
            $search = $this->select(implode('.', $keys));

        foreach ($search->data as $datakey => $dataval) {
            if (is_object($dataval))
                $test = $dataval->$field ?? null;
            else
                $test = $dataval[$field] ?? null;
            if (is_null($test))
                continue;
            switch ($op) {
                case '==':
                case '=': $res = $test == $val; break;
                case '!=': $res = $test != $val; break;
                case '<=': $res = $test <= $val; break;
                case '>=': $res = $test >= $val; break;
                case '<': $res = $test < $val; break;
                case '>': $res = $test > $val; break;
                case 'like': $res = preg_match($val, $test); break;
                default: $res = false;
            }
            if ($res)
                $set->data[$datakey] = &$this->data[$datakey];
        }
        return $set;
    }

    /**
     * Execute a search by combining a given expression with the search results of the
     * parent dataset. If the operator is "like", the value is a RegExp string.
     * @param string $field - the data field (may contains dots for multiple levels)
     * @param string $op - the operator, one of =, != <, >, <=, >=, like
     * @param bool|int|double|string $value - the value to compare to, must be scalar
     * @return Dataset
     */
    function orWhere(string $field, string $op, $val) : Dataset {
        if (!$this->parent)
            return $this;
        $set = $this->parent->where($field, $op, $val);
        // mix in the own data
        foreach ($this->data as $key => &$val)
            $set->data[$key] = $val;
        return $set;
    }

    /**
     * Return the first item of this dataset.
     * @return object|array|null
     */
    function first() : ?object {
        $this->_load();
        if (empty($data))
            return null;
        return $this->data[array_key_first($this->data)];
    }

    /**
     * Return the last item of this dataset.
     * @return object|array|null
     */
    function last() : ?object {
        $this->_load();
        if (empty($data))
            return null;
        return $this->data[array_key_last($this->data)];
    }

    /**
     * Select a field from this dataset. Returns a new Dataset containing
     * the field contents. Optionally, the field name may be dotted to
     * indicate sub-subfields. In this case, create a chain of Datasets
     * and return the last element.
     * @param string $field the field name, which can be a dotted name
     * @return Dataset
     */
    function select(string $field) : DataSet {
        $this->_load();
        $keys = explode('.', $field);
        $set = $this;
        while (!empty($keys))
            $set = new DataSubset($set->data, array_shift($keys), $set);
        return $set;
    }

    /**
     * Is this dataset empty?
     * @return bool
     */
    function empty() : bool {
        $this->_load();
        return empty($this->data);
    }

    /**
     * Return the number of records.
     * @return int
     */
    function count() : int {
        $this->_load();
        return count($this->data);
    }

    /**
     * Skip the first n results.
     * @param int $n
     * @return Dataset
     */
    function skip(int $n) : Dataset {
        $this->_load();
        $set = new Dataset();
        $set->parent = $this;
        foreach ($this->data as $key => &$val) {
            if ($n > 0)
                $n--;
            else
                $set->data[$key] = &$val;
        }
        return $set;
    }

    /**
     * Limit the results to n values.
     * @param int $n
     * @return Dataset
     */
    function limit(int $n) : Dataset {
        $this->_load();
        $set = new Dataset();
        $set->parent = $this;
        foreach ($this->data as $key => &$val) {
            if ($n === 0)
                break;
            $n--;
            $set->data[$key] = &$val;
        }
        return $set;
    }

    /**
     * Fetch the dataset's keys. These keys are always the keys
     * of the original dataset so updates can work across datasets.
     * @return array
     */
    function keys() : array {
        $this->_load();
        return array_keys($this->data);
    }

    /**
     * Fetch the dataset and return it.
     * @return array
     */
    function fetch() {
        $this->_load();
        return $this->data;
    }

    /**
     * Add the given data to the dataset. Optionally, supply the key
     * to use for the insert. If not supplied, the data is appended
     * to the array.
     * @param mixed $data
     * @param mixed|null $key
     * @return Dataset myself
     */
    function insert($data, ?string $key = null) : Dataset {
        $this->_load();
        if (!is_null($key))
            $key = max(array_keys($this->data));
        $this->data[$key] = &$data;
        $this->_autoflush();
        return $this;
    }

    /**
     * Update a dataset. The data is either an object or an array
     * whose data is merged into the existing dataset. The update
     * operation also affects all parent datasets, causing the file
     * to be updated at the end if autoflush is enabled. Note that
     * nothing happens if the dataset is empty.
     * @param array|object $data
     * @return Dataset
     */
    function update($data) : Dataset {
        $data = (array) $data;
        $this->_load();
        foreach ($this->data as &$val) {
            if (is_array($val))
                $val = array_merge($val, $data);
            else foreach ($data as $k => $v)
                $val->$k = $v;
        }
        $this->_autoflush();
        return $this;
    }

    /**
     * Insert multiple records into the database.
     * @param array $data
     * @return Dataset
     * @see update()
     */
    function insertMany(array $data) : Dataset {
        $save = $this->options['autoflush'];
        $this->options['autoflush'] = false;
        foreach ($data as $record)
            $this->insert($record);
        $this->options['autoflush'] = $save;
        $this->_autoflush();
        return $this;
    }

    /**
     * Sort the dataset. Note that this changes this dataset only,
     * not any parent or other datasets. If you want to sort by
     * index, use '*' as the field name. Note that objects and arrays are
     * not compared. When a scalar value is compared to an object or array,
     * the scalar value wins, and two objects or arrays are always assumed
     * to be equal.
     * 
     * @param string $key - the key to sort by, may contain dots
     * @param string $mode - either 'desc' or 'asc'
     * @return Dataset myself
     */
    function sort(string $key = '', string $mode = 'asc') : Dataset {
        $this->_load();
        if ($key === '*') {
            if ($mode === 'asc')
                ksort($this->data);
            else
                krsort($this->data);
        }
        else {
            $key = explode('.', $key);
            uasort($this->data, function($a, $b) use ($mode, $key) {
                $aa = $a;
                $bb = $b;
                foreach ($key as $kk) {
                    $ra = $aa->{$kk} ?? null;
                    $rb = $bb->{$kk} ?? null;
                    if (is_null($ra) || is_null($rb))
                        break;
                    $aa = $aa->{$kk};
                    $bb = $bb->{$kk};
                }
                if (is_null($ra))
                    $res = is_null($rb) ? 0 : 1;
                else if (is_null($rb))
                    $res = -1;
                if (!is_scalar($ra))
                    $res = !is_scalar($rb) ? 0 : 1;
                else if (!is_scalar($rb))
                    $res = -1;
                else
                    $res = strcmp((string) $ra, (string) $rb);
                return ($mode === 'desc') ? -$res : $res;
            });
        }
        $this->modified = true;
        $this->_autoflush();
        return $this;
    }

    /**
     * Delete the dataset and remove it from its parents.
     * If a field name is given (no dots!), then delete that
     * field only.
     * @param string $field the field to delete (opt)
     * @return Dataset myself
     */
    function delete(string $field = null) : Dataset {
        if (!is_null($field)) {
            foreach ($this->data as &$value) {
                if (is_object($value))
                    unset($value->$field);
                else
                    unset($value[$field]);
            }
        } else {
            $keys = $this->keys();
            for ($ds = $this->parent; $ds; $ds = $ds->parent) {
                foreach ($keys as $k)
                    unset($ds->data[$k]);
            }
        }
        $this->modified = true;
        $this->_autoflush();
        return $this;
    }

    /**
     * Flush the dataset regardsless of its modified state.
     */
    function flush() : void {
        for ($set = $this; $set; $set = $set->parent) {
            $set->_flush();
            $set->modified = false;
        }
    }

    /**
     * Lazy loader: Load the data if the $data member is null. 
     */
    protected function _load() : void {
        if (is_null($this->data))
            $this->data = [];
    }

    /**
     * Overloadable: flush the data to disk.
     */
    protected function _flush(): void {
    }

    /**
     * Helper: flush the dataset if autoflush is enabled.
     */
    private function _autoflush() : void {
        if ($this->modified && $this->options['autoflush'])
            $this->flush();
    }
}

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

/**
 * A File is a dataset that is backed up to disk.
 */
class File extends Dataset {
    /**
     * @var array
     */
    static protected $dfltOptions;
    /**
     * @var array
     */
    static protected $cache = [];

    /**
     * @var string
     */
    protected $path;
    /**
     * @var array
     */
    protected $options;

    /**
     * Return the default options. Side effect: Set them as well
     * if no options were set.
     */
    static function getOptions() : array {
        $options = [
            'path' => $_SERVER['DOCUMENT_ROOT'].'/data/*.json',
            'autoflush' => false,
            'autodelete' => false,
            'cache' => true,
            'lazyLoad' => true,
            'flags' => JSON_PRETTY_PRINT // JSON flags for saving	
        ];
        if (!self::$dfltOptions)
            self::$dfltOptions = $options;
        return self::$dfltOptions;
    }

    /**
     * Set global options. Merge them into the current options.
     */
    static function setOptions(array $options) : void {
        self::$dfltOptions = array_merge(self::getOptions(), $options);
    }

    /**
     * Create and return a new File object.
     * @param string $path - the path to the JSON file
     * @param array $options - an array of options
     * @return File
     */
    static function get(string $path, array $options = []) : File {
        $options = array_merge(self::getOptions(), $options);
        $set = $options['cache'] ? (self::$cache[$options['path']][$path] ?? null) : null;
        if (!$set) {
            $set = new File($path, $options);
            if ($options['cache'])
                self::$cache[$options['path']][$path] = $set;
        }
        return $set;
    }

    /**
     * Return the path name for a given path using the 'path' setting
     * in the options.
     * @param string $path - the path to the JSON file
     * @param array $options - an array of options
     * @return string
     */
    static function getPath($path, array $options = []) : string {
        $options = array_merge(self::getOptions(), $options);
        $p = realpath(dirname($path));
        return substr($path, strlen($p)) === $p
                    ? $path // an absolute path
                    : str_replace('*', $path, $options['path']);
    }

    /**
     * Clear the cache. The path is either empty, which clears the entire
     * cache, or it is a path; in the latter case, all cache entries whose
     * path start with the given path are cleared.
     * @param string $path
     * @param array $options - an array of options
     */
    static function clearCache(string $path = '', array $options = []) : void {
        $options = array_merge(self::getOptions(), $options);
        if ($path) {
            if (substr($path, -1) !== '/')
                $path .= '/';
            $len = strlen($path);
            foreach (self::$cache[$options['path']] as $key => $entry) {
                if (substr($key, 0, $len) === $path)
                    unset(self::$cache[$options['path']][$key]);
            }
        }
    }

    /**
     * List all files in the given sub-path, using the setting of the "path"
     * option. Return only the base file name suitable for the get() call.
     * @param string $path - the path to search
     * @param array $options - an array of options
     * @return array
     */
    static function list(string $path = '', array $options = []) : array {
        $options = array_merge(self::getOptions(), $options);
        $dir = $options['path'];
        if (strpos($dir, '*') === false)
            return [];
        $pattern = '#^'.str_replace('*', basename($dir), '.+?').'$#';
        $dir = dirname($dir);
        $dir .= '/'.$path;
        $out = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $entry) {
                if ($entry[0] !== '.') {
                    $path = $dir.'/'.$entry;
                    // Add only if the file name matches the path pattern
                    if (is_file($path) && preg_match("\n$pattern\n", $entry))
                        $out[] = pathinfo($entry, PATHINFO_FILENAME);
                }
            }
        }
        return $out;
    }

    /**
     * Create new wrapper.
     * @param string $path - the path to the JSON file
     * @param array $options - an array of options
     */
    function __construct(string $path, array $options = []) {
        parent::__construct();        
        $this->options = array_merge(self::getOptions(), $options);
        $this->path = self::getPath($path, $options);
        $this->data = null;
        if (!$this->options['lazyLoad'])
            $this->_load();
    }

    /**
     * Does this instance exist on disk?
     */
    public function exists() : bool {
        return is_file($this->path);
    }

    /**
     * Save the file under a different name.
     * @param string $path the sub-path, used with options['path']
     */
    public function saveAs(string $path) : void {
        $this->path = self::getPath($path, $this->options);
        $this->flush();
    }

    /**
     * Overloadable: Load the data from disk.
     * @throws Exception if the loaded JSON is invalid
     */
    protected function _load() : void {
        if (!is_null($this->data))
            return;
        $json = is_file($this->path) ? @file_get_contents($this->path) : null;
        if ($json) {
            $json = @json_decode($json);
            if (is_null($json))
                throw new Exception("File $this->path does not contain valid JSON");
        }
        else
            $json = [];
        $this->data = (array) $json;
    }
    
    /**
     * Overloaded: flush the data to disk. Delete the file if
     * there is no data.
     * @throws Exception on write errors
     */
    protected function _flush() : void {
        if ($this->options['autodelete'] && $this->empty() && is_file($this->path))
            @unlink($this->path);
        else {
            $dir = dirname($this->path);
            if (!is_dir($dir) && !mkdir($dir, 0777, true))
                throw new Exception("Cannot create folder $dir");
            else if (!file_put_contents($this->path, json_encode($this->data, $this->options['flags'])))
                throw new Exception('Cannot write file '.$this->path);
        }
        $this->modified = false;
    }
}

class Exception extends \Exception {}
