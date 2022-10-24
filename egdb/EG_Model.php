<?php

/**
 * Abstract model class for all models in the application.
 * @since 1.0.0
 */
 

/**
 * Exit if accessed directly
 */
defined('ABSPATH') or die('Direct Script not Allowed');



/**
 * Load required files
 */
require_once __DIR__ . '/EG_Query.php';


/**
 * Abstract model class
 */
abstract class Model
{
     /**
     * table
     * @var string
     */

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

     /**
     * _data
     * @var object
     */
    public $_data = null;

     /**
     * _rawData
     * @var object
     */
    protected $rawData = null; 


    # constructor
    function __construct($data = null)
    {
        // assign data if NOT null
        if ($data) {
            $this->_rawData = $data;
            
            $this->_data = $this->getter($data);
        } 
    }

    # default setter applied before insert or update
    function setter($data)
    {
        return $data;
    }

    # default getter applied after fetching row(s)
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

        // instantiate core query builder 
        $database = new \EGDB\Query($instance->table, [
            'primaryKey' => $instance->primaryKey,
            'created_at' => $instance->created_at,
            'updated_at' => $instance->updated_at,
            'deleted_at' => $instance->deleted_at, 
            'fields' => $instance->fields,
            'hidden' => $instance->hidden,
            'fillable' => $instance->fillable,
        ], $this->setter($instance->_data));


       
        // if method exists on query builder
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
        if (is_array($results) && count($results) > 0 && is_array($results[0])) {

            return array_map(function ($result) {
                return new static($result);
            }, $results);
        }

        // is object 
        if (is_array($results) && !($results) instanceof \EG_Query) {
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

    // get method non static
    public function __call($method, $arguments)
    {
        return $this->_load($this, $method, $arguments);
    }

    # get property 
    function __get($key)
    {

        if(method_exists($this, $key)) {
            $restrictedMethods = [
                '_setter',
                '_getter',
                '_data',
                '_rawData',
                'hasOne',
                'hasMany',
                'belongsTo',
                'belongsToMany',
            ];

            if (!in_array($key, $restrictedMethods)) {
                return $this->$key();
            }
        }

        if (isset($this->_data->$key)) {
            return $this->_data->$key;
        }
    }

    # set value
    function __set($key, $value)
    {
        if (isset($this->_rawData->$key)) {
            $this->_rawData->$key = $value;
        }

        $this->_data[$key] = $value; 
    }

    # get data
    public function data()
    {
        return $this->_data;
    }

    // has one
    public function hasOne($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $localKey = $localKey ?: $this->primaryKey;

        $model = new $model();
        $model->table = $model->table ?: $model->table;

        $results = $model->where($foreignKey . ' = ' . $this->$localKey)->first();

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

        $results = $model->where($localKey . ' = ' . $this->$foreignKey)->first();

        return $model->_format($results);
    }

    // belongs to many
    public function belongsToMany($model, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->primaryKey;
        $localKey = $localKey ?: $this->primaryKey;

        $model = new $model();
        $model->table = $model->table ?: $model->table;

        $results = $model->where($localKey . ' = ' . $this->$foreignKey)->get();

        return $model->_format($results);
    }
}
