<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 3/7/2015
 * Time: 1:03 PM
 */

class HDBTransaction extends Eloquent {

    protected $table = 'HDB_transaction';
    protected $primaryKey = 'HDB_transaction_id';

    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

}
