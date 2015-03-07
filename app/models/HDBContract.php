<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 3/7/2015
 * Time: 1:03 PM
 */

class HDBContract extends Eloquent {

    protected $table = 'HDB_contract';
    protected $primaryKey = 'HDB_contract_id';

    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

}
