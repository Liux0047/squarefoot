<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 3/7/2015
 * Time: 1:35 PM
 */

class StreetName extends Eloquent{

    protected $table = 'street_name';
    protected $primaryKey = 'street_name_id';

    public $timestamps = false;
}