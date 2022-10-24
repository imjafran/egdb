<?php

class Table extends \EG_Model
{
    protected $table = 'tables';

    protected $hidden = [];

    public function reservations()
    {
        return $this->hasMany('\Reservation', 'table_id');
    }
}
