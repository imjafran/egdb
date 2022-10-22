<?php

/**
 * Abstract class for all EVR SaaS classes
 * Base Query
 * @since 1.0.0
 */


# Namespace
namespace EGDB;

# Exit if accessed directly
defined('ABSPATH') or die('Direct Script not Allowed');

// abstract model class works with WPDB

class Query
{
    /**
     * table prefix
     * @var string
     */
    protected $prefix = '';

    /**
     * table name
     * @var string
     */
    protected $table = '';

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
     * RAW query
     * @var array
     */
    protected $query = null;

    /**
     * action type
     * @var string
     */
    protected $type = 'select';

    /**
     * Select fields
     * @var array || string 
     * 
     */
    protected $select = '*';

    /**
     * Where clause
     * @var array
     * Example: ('id', 1, '=') or ('id', 1)
     */
    protected $whereAnd = [];

    /**
     * Where clause
     * @var array
     * Example: ('id', 1, '=') or ('id', 1)
     */
    protected $whereOr = [];

    /**
     * Order By
     * @var String
     * Example: 'id' 
     */
    protected $orderBy = null;


    /**
     * Order
     * @var String
     * Example: 'ASC' or 'DESC' 
     */

    /**
     * Limit
     * @var int
     */
    protected $limit = null;

    /**
     * Offset
     * @var int
     */
    protected $offset = null;

    /**
     * Group By
     * @var String
     * Example: 'id' 
     */
    protected $groupBy = null;

    /**
     * Join
     * @var Boolean
     */

    protected $join = null;
    /**
     * Join Type
     * @var String
     * Example: 'INNER' or 'LEFT' or 'RIGHT'  
     */
    protected $joinType = null;

    /**
     * Join Table
     * @var String
     * Example: 'table_name' 
     */
    protected $joinTable = null;

    /**
     * Join Condition
     * @var String
     * Example: 'table_name.id = table_name.id' 
     */
    protected $joinOn = null;

    /**
     * RAW Data
     * @var Array
     * Example: ['id' => 1, 'name' => 'test']  
     */
    public $data = [];

    /**
     * Number of rows found
     */
    public $foundRows = 0;

    /**
     * Number of rows affected
     * @var int
     */
    protected $affectedRows = 0;

    /**
     * lastQuery
     */
    public $lastQuery = '';

    /**
     * Data setter before insert or update
     */
    protected $setter_callback = null;

    # Constructor
    function __construct($table = '', $args = null)
    {
        $this->table = $table;

        if ($args) {
            $this->primaryKey = $args['primaryKey'] ?? null;
            $this->created_at = $args['created_at'] ?? null;
            $this->updated_at = $args['updated_at'] ?? null;
            $this->deleted_at = $args['deleted_at'] ?? null;
            $this->setter_callback = $args['setter'] ?? null;   
        }
    }

    # Set table
    function setTable($table = '')
    {
        $this->table = $table;
    }

    # Get Table Name
    function getTable()
    {
        global $wpdb;
        return $wpdb->prefix . $this->prefix . $this->table;
    }

    # RAW query
    function query($query)
    {
        $this->query = $query;
        return $this;
    }

    # Where
    function where($conditions = [], $whereType = 'and')
    {
        if ($whereType == 'or') {
            $this->whereOr = is_array($conditions) ? $conditions : [$conditions];
        } else {
            $this->whereAnd = is_array($conditions) ? $conditions : [$conditions];
        }
        return $this;
    }

    # whereOr 
    function whereOr($wheres)
    {
        $this->where($wheres, 'or');
        return $this;
    }

    # whereAnd
    function whereAnd($wheres)
    {
        $this->where($wheres, 'and');
        return $this;
    }

    # Order By
    function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    # Order
    function order($order)
    {
        $this->order = $order;
        return $this;
    }

    # Limit
    function limit($limit, $offset = null)
    {
        if ($offset) {
            $this->limit = $offset;
            $this->offset = $limit;
        } else {
            $this->limit = $limit;
        }
    }

    # offset
    function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    # Group By
    function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    # Join
    function join($joinTable, $joinOn, $joinType = 'left')
    {
        $this->join = true;
        $this->joinType = $joinType;
        $this->joinTable = $joinTable;
        $this->joinOn = $joinOn;
        return $this;
    }

    # Inner Join
    function innerJoin($joinTable, $joinOn)
    {
        $this->join($joinTable, $joinOn, 'inner');
        return $this;
    }

    # Left Join
    function leftJoin($joinTable, $joinOn)
    {
        $this->join($joinTable, $joinOn, 'left');
    }

    # Right Join
    function rightJoin($joinTable, $joinOn)
    {
        $this->join($joinTable, $joinOn, 'right');
    }

    # get query
    function getQuery()
    {
        $table = $this->getTable();

        $query = '';

        switch ($this->type) {
            case 'select':
                $query = "SELECT {$this->select} FROM {$table}";
                break;
            case 'insert':
                $query = "INSERT INTO {$table} SET " . $this->setFields($this->data);
                break;
            case 'update':
                $query = "UPDATE {$table} SET " . $this->setFields($this->data);
                break;
            case 'delete':
                $query = "DELETE FROM {$table}";
                break;
            default:
                $query = "SELECT {$this->select} FROM {$table}";
                break;
        }

        if ($this->join) {
            $query .= " {$this->joinType} JOIN {$this->joinTable} ON {$this->joinOn}";
        }

       # where and
        if ($this->whereAnd && count($this->whereAnd) > 0) {
            $query .= " WHERE " . $this->setWhere($this->whereAnd, 'and');
        }

        # where or
        if ($this->whereOr && count($this->whereOr) > 0) {
            $query .= " WHERE " . $this->setWhere($this->whereOr, 'or');
        }
        

        if ($this->groupBy) {
            $query .= " GROUP BY {$this->groupBy}";
        }

        if ($this->orderBy) {
            $query .= " ORDER BY {$this->orderBy}";
        }

        if ($this->order) {
            $query .= " {$this->order}";
        }

        if ($this->limit) {
            $query .= " LIMIT {$this->limit}";
        }

        if ($this->offset) {
            $query .= " OFFSET {$this->offset}";
        } 

        // prepare 
        $this->query = $this->prepare($query);

        return $this->query;
    } 

    #setWhere
    function setWhere($wheres, $whereType = 'and')
    {
        $where = '';
        $whereArray = [];
        foreach ($wheres as $key => $value) {
            if (is_array($value)) {
                $whereArray[] = $this->setWhere($value, $key);
            } else {
                $whereArray[] = $value;
            }
        }
        $where = implode(" {$whereType} ", $whereArray);
        return $where;
    }
    
    # Set Items
    function setFields($data)
    {
        $items = '';
        foreach ($data as $key => $value) {
            $items .= "{$key} = '{$value}',";
        }
        return rtrim($items, ',');
    }

    # Prepare Variables
    function prepare($query = '')
    {
        global $wpdb;
        $query = $wpdb->prepare($query, '');
        $query = $wpdb->remove_placeholder_escape($query);
        return $query;
    }

    # Get RAW query
    function getRawQuery()
    {
        return $this->query;
    }

    # get prepared data
    function getPreparedData($data = null)
    {
        if ($data) {
            $this->data = $data;
        }

        $setter_callback = $this->setter_callback;

        if (is_callable($setter_callback)) {
            $this->data = call_user_func($setter_callback, $this->data);
        }

        return $this->data;
    }

    # execute query
    function execute()
    {
        global $wpdb;

        $query = $this->getQuery(); 

        if ($this->type == 'select') {
            // select 
            $result = $wpdb->get_results($query); 

        } elseif ($this->type == 'insert') {
            $result = $wpdb->insert($this->getTable(), $this->getPreparedData());
        } elseif ($this->type == 'update') {

            if ($this->updated_at) {
                $this->data['updated_at'] = gmdate('Y-m-d H:i:s');
            }

            $result = $wpdb->update($this->getTable(), $this->getPreparedData(), $this->where);
        } elseif ($this->type == 'delete') {
            $result = $wpdb->delete($this->getTable(), $this->where);
        } else {
            $result = $wpdb->query($query);
        }

        $this->reset();

        return  $result;
    }

    # reset
    function reset()
    {
        $this->query = null;
        $this->where = [];
        $this->orderBy = null;
        $this->order = null;
        $this->limit = null;
        $this->offset = null;
        $this->groupBy = null;
        $this->join = false;
        $this->joinType = null;
        $this->joinTable = null;
        $this->joinOn = null;
        $this->type = 'select';
        $this->data = [];
    }

    # Select
    function select($select)
    {
        $this->type = 'select';
        $this->select = is_array($select) ? implode(',', $select) : $select;
        return $this;
    }

    # Get
    function get()
    {
        return $this->execute();
    }

    # Get One
    function one()
    {
        $this->limit(1);
        $results = $this->execute(); 

        if ( is_array($results) && count($results) > 0 ) {
            $results = $results[0];
            return $results;
        } else {
            return null;
        }
    }

    # Get all
    function all()
    {
        $this->reset();
        return $this->execute();
    }

    # find 
    function find($id)
    {
        $this->type = 'select';
        $this->where("{$this->primaryKey} = {$id}");
        return $this->one();
    }

    # first
    function first()
    {
        $this->type = 'select';
        $this->orderBy($this->primaryKey);
        $this->order('ASC');
        $this->limit(1);
        return $this->one();
    }

    # last
    function last()
    {
        $this->type = 'select';
        $this->orderBy($this->primaryKey);
        $this->order('DESC');
        $this->limit(1);
        return $this->one();
    }

    # soft delete
    function softDelete($id = null)
    {
        
        $this->type = 'update';

        if ($id) {
            $this->where("{$this->primaryKey} = {$id}");
        } else {
            $this->where("{$this->primaryKey} = {$this->id}");
        }

        $this->data = [
            $this->deleted_at => current_time('mysql')
        ];

        return $this->execute();
    }

    # delete
    function delete($id)
    {
        $this->where("{$this->primaryKey} = {$id}");
        $this->type = 'delete';

        return $this->execute();
    }

    # insert
    function insert($data)
    {
        $this->type = 'insert';

        $this->data = $data;

        if ($this->created_at) {
            $this->data['created_at'] = gmdate('Y-m-d H:i:s');
        }

        return $this->execute();
    }

    # update
    function update($data, $where = null, $whereType = 'AND')
    {
        $this->type = 'update';

        $this->data = $data;

        if ($this->updated_at) {
            $this->data['updated_at'] = gmdate('Y-m-d H:i:s');
        }

        if ($where) {
            $this->where($where, $whereType);
        } elseif (isset($data[$this->primaryKey])) {
            $this->where("{$this->primaryKey} = {$data[$this->primaryKey]}");
        } 
        return $this->execute();
    }

    # Get last insert id
    function lastInsertId()
    {
        global $wpdb;
        return $wpdb->insert_id;
    }


    # exists
    function exists($id)
    {
        return !is_null($this->find($id));
    }

    # Count
    function count()
    {
        $this->select('count(*) as total');
        $result = $this->execute();
        return $result->result[0]['total'];
    }

    # Search in
    function search($search, $fields = [])
    {
        $this->type = 'select';
        $this->where($this->searchQuery($search, $fields));
        return $this;
    }

    # Search query
    function searchQuery($search, $fields = [])
    {
        $search = trim($search);
        $search = explode(' ', $search);
        $search = array_filter($search);
        $search = array_map('trim', $search);
        $search = array_map('esc_sql', $search);
        $search = array_map('sanitize_text_field', $search);
        $search = array_map('strtolower', $search);

        $search = array_map(function ($item) {
            return "%{$item}%";
        }, $search);

        $search = array_map(function ($item) use ($fields) {
            $search = [];
            foreach ($fields as $field) {
                $search[] = "{$field} LIKE '{$item}'";
            }
            return implode(' OR ', $search);
        }, $search);

        return implode(' AND ', $search);
    }

    # rows
    function rows()
    {
        global $wpdb;
        return $wpdb->num_rows;
    }

    # Affected rows
    function affectedRows()
    {
        global $wpdb;
        return $wpdb->rows_affected;
    }

    # last query
    function lastQuery()
    {
        global $wpdb;
        return $wpdb->last_query;
    }

    # last error
    function lastError()
    {
        global $wpdb;
        return $wpdb->last_error;
    }

}
