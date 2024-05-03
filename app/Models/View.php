<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of SyncFailur
 *
 * @author rizwan
 */
class View extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';
    protected $connection = "stats_db";

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'views';
    protected $fillable = ["object_id", "object_type", "relation_id", "relation_type", "ip_address", "lat", "lon", "created_at", "updated_at"];

    /**
     * Save views (Post + Box + Profile)
     * @param type $inputs
     * @return type
     */
    public static function saveView($inputs) {
        $view = new View($inputs);
        return $view->save();
        ;
    }

}
