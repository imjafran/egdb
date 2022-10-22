<?php 

class Reservation extends \EGDB\Model
{
    protected $table = 'reservations';

    public function setter($data)
    {
        return $data;
    }

    public function getter($data)
    { 
        $data->id = (int) $data->id;
        $data->table_id = (int) $data->table_id;
        $data->guests = (int) $data->guests;
        return $data;
    }

    public function table()
    {
        return $this->belongsTo('\Table', 'table_id');
    }


}