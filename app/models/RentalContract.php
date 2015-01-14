<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/14/2015
 * Time: 10:49 AM
 */

class RentalContract extends Eloquent{
    protected $table = 'rental_contract';
    protected $primaryKey = 'rental_contract_id';

    public $timestamps = false;
}