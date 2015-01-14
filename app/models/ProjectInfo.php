<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 1/14/2015
 * Time: 1:44 PM
 */

class ProjectInfo extends Eloquent {

    protected $table = 'project_info';
    protected $primaryKey = 'project_info_id';

    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

}
