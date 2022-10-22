<?php

/**
 * Abstract class for all EVR SaaS classes
 * Base Model
 * @since 1.0.0
 */


/**
 * Namespace
 */
namespace EGDB;


/**
 * Exit if accessed directly
 */
defined('ABSPATH') or die('Direct Script not Allowed');


/**
 * Define
 */

define('EGDB_VERSION', '1.0.0');
define('EGDB_PATH', plugin_dir_path(__FILE__));

/**
 * Load files
 */
require_once __DIR__ . '/Query.php';


// abstract model class
abstract class Model
{
    protected $table = null;

     /**
     * primary key
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * created at
     * @var string
     */
    protected $created_at = 'created_at';

    /**
     * updated at
     * @var string
     */
    protected $updated_at = 'updated_at';

    /**
     * deleted at
     * @var string
     */
    protected $deleted_at = 'deleted_at';


    public $data = null;

    protected $rawData = null;


    protected $foundRows = null;
    protected $AffectedRows = null;
    protected $lastQuery = null;

    # constructor
    function __construct($data = null)
    {
        if ($data) {
            $this->rawData = $data;
            $this->data = $this->getter($data);
        } 
    }

    # default setter applied before insert or update
    function setter($data)
    {
        return $data;
    }

    # default getter
    function getter($data)
    {
        return $data;
    }

    /**
     * Load data from database
     */
    public function _load($instance, $method = '', $arguments = null)
    {

        /**
         * Terminates if no table is set
         */
        if (!$this->table) {
            throw new \Exception('Table not set');
        }

        $database = new Query($instance->table, [
            'primaryKey' => $instance->primaryKey,
            'created_at' => $instance->created_at,
            'updated_at' => $instance->updated_at,
            'deleted_at' => $instance->deleted_at, 
            'setter' => [$instance, 'setter'],
        ]); 

       
        if (method_exists($database, $method)) {
            $results = $database->$method(...$arguments);
 
            return $this->_format($results);
        } else {
            throw new \Exception('method does not exists');
        }
    }

    /**
     * Format data
     */
    public function _format($results)
    {
        // is array 
        if (is_array($results) && count($results) > 0 && is_object($results[0]) ) {

            return array_map(function ($result) {
                return new static($result);
            }, $results);
        }

        // is object 
        if (is_object($results) && !($results) instanceof \EGDB\Query) {
            return new static($results);
        }

        return $results;
    }

    // get static method
    public static function __callStatic($method, $arguments)
    {
        $object = new static(); 
        return $object->_load($object, $method, $arguments);
    }

    // get method
    public function __call($method, $arguments)
    { 
        return $this->_load($this, $method, $arguments);
    }

    # get data 
    function __get($key)
    {

        if(method_exists($this, $key)) {
            $restrictedMethods = [
                'setter',
                'getter',
                'data',
                'rawData',
                'foundRows',
                'AffectedRows',
                'lastQuery', 
            ];

            if (!in_array($key, $restrictedMethods)) {
                return $this->$key();
            }
        }

        if (isset($this->data->$key)) {
            return $this->data->$key;
        }
    }

    # set value
    function __set($key, $value)
    {
        if (isset($this->rawData->$key)) {
            $this->rawData->$key = $value;
        }
    }

    # get data
    public function data()
    {
        return $this->data;
    }

    // has one
    public function hasOne($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $localKey = $localKey ?: $this->primaryKey;

        $model = new $model();
        $model->table = $model->table ?: $model->table;

        $results = $model->where($foreignKey, $this->$localKey)->first();

        return $model->_format($results);
    }

    // has many
    public function hasMany($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $localKey = $localKey ?: $this->primaryKey;

        $model = new $model(); 
        $model->table = $model->table ?: $model->table; 
 
        $results = $model->where($foreignKey . ' = ' . $this->$localKey)->get();

        return $model->_format($results);
    }

    // belongs to
    public function belongsTo($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $localKey = $localKey ?: $this->primaryKey;

        $model = new $model();
        $model->table = $model->table ?: $model->table;

        $results = $model->where($localKey, $this->$foreignKey)->first();

        return $model->_format($results);
    }

    // belongs to many
    public function belongsToMany($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $localKey = $localKey ?: $this->primaryKey;

        $model = new $model();
        $model->table = $model->table ?: $model->table;

        $results = $model->where($localKey, $this->$foreignKey)->get();

        return $model->_format($results);
    } 

}