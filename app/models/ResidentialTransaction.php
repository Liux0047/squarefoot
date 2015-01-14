<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/14/2015
 * Time: 2:16 PM
 */

class ResidentialTransaction extends Eloquent{

    protected $table = 'residential_transaction';
    protected $primaryKey = 'residential_transaction_id';

    public $timestamps = false;
}