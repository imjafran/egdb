<?php 

class Reservation extends EG_Model {
    protected $table = 'reservations'; 

    public function table()
    {
        return $this->belongsTo('\Table', 'table_id');
    }

}