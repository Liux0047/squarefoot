<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/14/2015
 * Time: 1:11 PM
 */

class CondoName extends Eloquent
{

    protected $table = 'condo_name';
    protected $primaryKey = 'condo_name_id';

    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
}
