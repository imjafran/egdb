<?php

/**
 * Abstract base class for all models 
 * @since 1.0.0
 */
 

/**
 * Exit if accessed directly
 */
defined('ABSPATH') or die('Direct Script not Allowed');

/**
 * Load files
 */
require_once __DIR__ . '/EG_Query.php';


// abstract model class
abstract class EG_Model
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

    /**
     * fields
     * @var array 
     */
    protected $fields = []; 

    /**
     * hidden fields
     * @var array 
     */
    protected $hidden = [];

    /**
     * fillable fields
     * @var array 
     */
    protected $fillable = [];


    /**
     * data
     * @var array 
     */
    public $_data = [];

    # constructor
    function __construct($data = null)
    {
        if ($data) { 

            $array_data = array_map(function ($value) {
                switch (true) {
                    case is_numeric($value):
                        return (int) $value;
                        break;

                    case is_bool($value):
                        return (bool) $value;
                        break;

                    default:
                        return maybe_unserialize($value);
                        break;
                }
            }, $data);

            $this->_data = $this->getter( $array_data ); 

            $hidden = $this->hidden;

            if( !empty($hidden) ) {
                foreach ($hidden as $key) {
                   if( isset($this->_data[$key]) ) {
                        unset($this->_data[$key]);
                   }
                }
            }
 
        }

        return $this;
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

        $database = new \EG_Query($instance->table, [
            'primaryKey' => $instance->primaryKey,
            'created_at' => $instance->created_at,
            'updated_at' => $instance->updated_at,
            'deleted_at' => $instance->deleted_at, 
            'fields' => $instance->fields,
            'hidden' => $instance->hidden,
            'fillable' => $instance->fillable,
        ], $this->setter($instance->_data));


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

    // get method
    public function __call($method, $arguments)
    {
        return $this->_load($this, $method, $arguments);
    }

    # get property 
    function __get($key)
    {

        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }

        return null;
    }

    # set value
    function __set($key, $value)
    {
        $fillable = $this->fillable;
        
        if (count($fillable) > 0 && !in_array($key, $fillable)) {
            throw new \Exception('Field not fillable');
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
