<?php

namespace Daumling\Dataset;

require_once 'Dataset.php';
require_once 'Exception.php';

/**
 * A File is a dataset that is backed up by disk storage.
 * 
 * Due to the nature of JSON, associative arrays are stored as objects.
 * If an object is loaded, the code checks the object's properties for
 * being arrays or objects. If all property values are non-scalar, the
 * object is loaded as an associative array, mimicking indexed storage
 * of objects. If a property value is scalar, however, the entire object
 * is loaded as a single entry. If you save such an object, it will also
 * be saved as a single-element array.
 * 
 * This class supports file caching and the file-related options. Other
 * storage backends can be implemented easily by overloading the Dataset
 * functions _load() and _flush().
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
        if (!self::$dfltOptions) {
            // root folder
            $dir = dirname(__DIR__);
            $pos = strpos($dir, '/vendor/');
            if ($pos !== false)
                $dir = substr($dir, 0, $pos);
                self::$dfltOptions = [
                'path' => $dir.'/data/*.json',
                'autoflush' => false,
                'autodelete' => false,
                'cache' => true,
                'flags' => JSON_PRETTY_PRINT // JSON flags for saving	
            ];
        }
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
        $pattern = "/^".str_replace('*', '(.+?)', basename($dir))."$/";
        $dir = dirname($dir);
        $dir .= '/'.$path;
        $out = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $entry) {
                if ($entry[0] !== '.') {
                    $path = $dir.'/'.$entry;
                    // Add only if the file name matches the path pattern
                    if (is_file($path) && preg_match($pattern, $entry, $matches))
                        $out[] = $matches[1];
                }
            }
        }
        return $out;
    }

    /**
     * Create a new File instance.
     * @param string $path - the sub-path to the JSON file, will be merged with toptions['path']
     * @param array $options - an array of options
     */
    function __construct(string $path, array $options = []) {
        parent::__construct();        
        $this->options = array_merge(self::getOptions(), $options);
        $this->path = self::getPath($path, $options);
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
     * Load the data from disk. Note that if the
     * data is an object, the property names will only become keys
     * if all property values are either arrays or objects. Otherwise,
     * the JSON will be stored as a single object.
     * @throws Exception if the loaded JSON is invalid
     */
    private function _load() : void {
        $json = is_file($this->path) ? @file_get_contents($this->path) : null;
        if ($json) {
            $json = @json_decode($json, false);
            if (is_null($json))
                throw new Exception("File $this->path does not contain valid JSON");
            if (is_object($json)) {
                foreach ($json as $value) {
                    if (is_scalar($value)) {
                        // must be a single object
                        $this->data = [$json];
                        return;
                    }
                }
            }
            $this->data = (array) $json;
        }
        else
            $this->data = [];
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
            if (!file_put_contents($this->path, json_encode($this->data, $this->options['flags'])))
                throw new Exception('Cannot write file '.$this->path);
        }
        $this->modified = false;
    }
}
