<?php 

class Table extends \EGDB\Model
{
    protected $table = 'tables';

    public function setter($data)
    {
        return $data;
    }

    public function getter($data)
    { 
        $data->id = (int) $data->id; 
        return $data;
    }

    public function reservations()
    {
        return $this->hasMany('\Reservation', 'table_id');
    }
}