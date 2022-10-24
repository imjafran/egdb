<?php 

class Table extends EG_Model
{
    protected $table = 'tables'; 

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function reservations()
    {
        return $this->hasMany('\Reservation', 'table_id');
    }
}