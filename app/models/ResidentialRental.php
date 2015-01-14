<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/14/2015
 * Time: 2:04 PM
 */

class ResidentialRental extends Eloquent{

    protected $table = 'residential_rental';
    protected $primaryKey = 'residential_rental_id';

    public $timestamps = false;
}