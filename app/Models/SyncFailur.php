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
class SyncFailur extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';
    protected $posts;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sync_failures';
    protected $fillable = ["user_id", "type", "object_id", "payload", "failure", "status", "microservice", "status_code", "total_tries", "created_at", "updated_at"];

    /**
     * Check user post sync failure exists
     * @param type $box_id
     * @return boolean
     */
    public static function isBoxPostFailureExits($box_id = 0, $user_id = 0) {
        if (env('APP_ENV') == 'production' && !in_array($user_id, config("general.production_test_users"))) {
            return true;
        }
        return true; // Only to redirect to node
        $query = static::where(function($sql) use($box_id) {
                    $sql->where("box_id", "=", $box_id);
                    $sql->where("status", "<>", 'done');
                });
        if ($query->exists()) {
            return true;
        }
        return false;
    }

    /**
     * Check user post sync failure exists
     * @param type $user_id
     * @return boolean
     */
    public static function isUserPostFailureExits($user_id = 0, $login_id = 0) {
        if (env('APP_ENV') == 'production' && !in_array($login_id, config("general.production_test_users"))) {
            return true;
        }
        return true; // Only to redirect to node
        $query = static::where(function($sql) use($user_id) {
                    $sql->where("user", "=", $user_id);
                    $sql->where("status", "<>", 'done');
                });
        if ($query->exists()) {
            return true;
        }
        return false;
    }

}
