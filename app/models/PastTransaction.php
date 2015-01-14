<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/14/2015
 * Time: 10:47 AM
 */

class PastTransaction extends Eloquent {

    protected $table = 'past_transaction';
    protected $primaryKey = 'past_transaction_id';

    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

}
