<?php

/**
 * EGDB Core Class as Query Builder
 * Base Query
 * @since 1.0.0
 */

# Exit if accessed directly
defined('ABSPATH') or die('Direct Script not Allowed');


/**
 * Core EGDB Query Class 
 */

final class Query
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
    protected $selects = [];

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
     * RAW Data
     * @var Array
     * Example: ['id' => 1, 'name' => 'test']  
     */
    public $data = [];

    /**
     * Data setter before insert or update
     */
    protected $setter_callback = null;

    # Constructor
    function __construct($table = '', $args = null, $data)
    {
        $this->table = $table;

        if ($args) {
            $this->primaryKey = $args['primaryKey'] ?? null;
            $this->created_at = $args['created_at'] ?? null;
            $this->updated_at = $args['updated_at'] ?? null;
            $this->deleted_at = $args['deleted_at'] ?? null;

            $this->fields = $args['fields'] ?? []; 
            $this->hidden = $args['hidden'] ?? [];
            $this->fillable = $args['fillable'] ?? [];
        }


        if (isset($data)) {

            // unset created_at, updated_at, deleted_at if exist 
            if (isset($data[$this->created_at])) {
                unset($data[$this->created_at]);
            }

            if (isset($data[$this->updated_at])) {
                unset($data[$this->updated_at]);
            }

            if (isset($data[$this->deleted_at])) {
                unset($data[$this->deleted_at]);
            }

            // set data
            $this->data = $data; 
        }
    }

    # Set table
    function setTable($table = '')
    {
        $this->table = $table;
    }

    # Get Table Name
    protected function getTable()
    {
        global $wpdb;
        return $wpdb->prefix . $this->prefix . $this->table;
    }

    # get fields
    protected function getFields()
    {
        $fields = $this->fields;

        if ($fields && !empty($fields)) {
            $fields[$this->primaryKey] = [
                'type' => 'int(11)',
                'null' => false,
                'default' => null,
                'auto_increment' => true,
                'primary_key' => true,
                'unique' => true,
                'unsigned' => true,
            ];
        }

        return $fields;
    }

    # get editable fields
    protected function getEditableFields()
    {
        return $this->editable;
    }

    # get hidden fields
    protected function getHiddenFields()
    {
        return $this->hidden;
    }

    # get fillable fields
    protected function getFillableFields()
    {
        return $this->fillable;
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
        $conditions = is_array($conditions) ? $conditions : [$conditions];

        if ($whereType == 'or') {
            $this->whereOr = array_merge($this->whereOr, $conditions);
        } else {
            $this->whereAnd = array_merge($this->whereAnd, $conditions);
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
    function join($joinTable, $joinOn, $joinType = 'LEFT')
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

        $data = $this->data;

        $query = '';

        switch ($this->type) {

            case 'insert':
                $query = "INSERT INTO {$table} " . $this->getInsertQuery($data);
                break;

            case 'update':
                $query = "UPDATE {$table} SET " . $this->setFields($data);
                break;

            case 'delete':
                $query = "DELETE FROM {$table}";
                break;

            case 'select':
            default:

                $query = "SELECT " . $this->getSelectFields() . " FROM {$table}";
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
    protected function setWhere($wheres, $whereType = 'and')
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
    protected function setFields($data)
    {
        $data = $this->getPreparedData($data);

        $items = [];
        foreach ($data as $key => $value) {

            switch (true) {
                case is_numeric($value):
                    $items[] = "`{$key}` = {$value}";
                    break;

                case is_null($value):
                    $items[] = "`{$key}` = NULL";
                    break;

                case is_bool($value):
                    $items[] = "`{$key}` = " . ($value ? '1' : '0');
                    break;

                case is_array($value):
                case is_object($value):
                    $items[] = "`{$key}` = '" . maybe_serialize($value) . "'";
                    break;

                default:
                    $items[] = "`{$key}` = '" . esc_sql($value) . "'";
                    break;
            }
        }
        return implode(', ', $items);
    }

    # Set Insert Query
    protected function getInsertQuery($data)
    {
        $data = $this->getPreparedData($data);

        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "`{$key}`";
            switch (true) {
                case is_numeric($value):
                    $values[] = $value;
                    break;

                case is_null($value):
                    $values[] = "NULL";
                    break;

                case is_bool($value):
                    $values[] = ($value ? '1' : '0');
                    break;

                case is_array($value):
                case is_object($value):
                    $values[] = "'" . maybe_serialize($value) . "'";
                    break;

                default:
                    $values[] = "'" . esc_sql($value) . "'";
                    break;
            }
        }
        return '(' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
    }

    # Get Select Fields
    protected function getSelectFields()
    {
        if ($this->selects && count($this->selects) > 0) {
            return implode(', ', array_map(function ($select) {
                
                if( is_array ($select)) {
                    return implode(', ', $select);
                }

                return $select;
                
            }, $this->selects));
        }

        return '*';
    }

    # Prepare Variables
    protected function prepare($query = '')
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
        if (!$data) {
            $data = $this->data;
        }

        // unset primary key 
        if (isset($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        }

        switch ($this->type) {
            case 'insert':
                if ($this->created_at) {
                    $data[$this->created_at] = current_time('mysql');
                }

                break;
            case 'update':
                if ($this->updated_at) {
                    $data[$this->updated_at] = current_time('mysql');
                }
                break;
            case 'delete':
                if ($this->deleted_at) {
                    $data[$this->deleted_at] = current_time('mysql');
                }
                break;
            default:
                break;
        }

        return $data;
    }

    # execute query
    function execute()
    {
        global $wpdb;

        $query = $this->getQuery();

        switch ($this->type) {

            case 'select':
                $result = $wpdb->get_results($query, ARRAY_A);
                break;

            case 'insert':
            case 'update':
            case 'delete':
            default:
                $result = $wpdb->query($query);

                if ($result && $this->type == 'insert') {
                    $result = $wpdb->insert_id;
                }

                break;
        }

        $this->reset();

        return $result;
    }

    # reset
    protected function reset()
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
        $this->selects = [];
        $this->data = [];
    }

    # Select

    function select()
    {
        $args = func_get_args();

        if (count($args) > 0) {

            $fields = array_map(function ($field) {
                if (is_array($field)) {
                    if (isset($field[0]) && isset($field[1])) {
                        return $this->getTable() . '.' . $field[0] . ' as ' . $field[1];
                    } 

                    return implode(', ', $field);
                } 

                return $field;
            }, $args);

            $this->selects[] = $fields;
        }

        return $this;
    }

    # Select
    function selectFrom($selects, $table = null)
    {
        if (is_array($selects)) {

            $selects = array_map(function ($field) use ($table) {

                if (is_array($field)) {

                    if (isset($field[1])) {
                        if ($table) {
                            return $table . '.' . $field[0] . ' AS ' . $field[1];
                        }

                        return $field[0] . ' AS ' . $field[1];
                    }

                    return implode(', ', $field);
                }

                if ($table) {
                    return $table . '.' . $field;
                }

                return $field;
            }, $selects);



            $this->selects[] = $selects;
        } else {
            if ($table) {
                $this->selects[] = $table . '.' . $selects;
            }


            $this->selects[] = $selects;
        }


        return $this;
    }

    # Get
    function get()
    {
        $this->type = 'select';
        return $this->execute();
    }

    # Get One
    function one()
    {

        $this->type = 'select';
        $this->limit(1);

        $results = $this->execute();

        if (is_array($results) && count($results) > 0) {
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


    # hard delete
    function hardDelete($id = null)
    {
        $this->type = 'delete';

        if ($id) {
            $this->where("{$this->primaryKey} = {$id}");
        } else {
            $this->where("{$this->primaryKey} = {$this->data[$this->primaryKey]}");
        }

        return $this->execute();
    }

    # soft delete
    function softDelete($id = null)
    {
        if ($this->deleted_at) {

            $this->data[$this->deleted_at] = current_time('mysql');

            $this->type = 'update';

            if ($id) {
                $this->where("{$this->primaryKey} = {$id}");
            } else {
                $this->where("{$this->primaryKey} = {$this->data[$this->primaryKey]}");
            }

            return $this->execute();
        }

        return false;
    }




    # soft delete
    function delete($id = null)
    {

        if ($this->deleted_at) {

            $this->softDelete($id);
        } else {
            $this->hardDelete($id);
        }
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

        if ($where) {
            $this->where($where, $whereType);
        } elseif (isset($data[$this->primaryKey])) {
            $this->where("{$this->primaryKey} = {$data[$this->primaryKey]}");
        } 

        return $this->execute();
    }

    # save
    function save()
    {

        if (isset($this->data[$this->primaryKey])) {
            return $this->update($this->data);
        } else {
            return $this->insert($this->data);
        }
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
    protected function searchQuery($search, $fields = [])
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

