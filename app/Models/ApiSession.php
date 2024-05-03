<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Description of Box
 *
 * @author rizwan saleem
 */
class ApiSession extends Model {

    protected $primarykey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'api_session';
    protected $fillable = [
        'key', 'object_id', 'type', 'category_id', 'timestamp', 'ip_address',
        "tt", 'lat', 'lon', 'created_at', 'updated_at', 'api_offset', 'api_service'
    ];

    public static function saveUserHomeSession($session, $user_id) {
        $session['object_id'] = $user_id;
        $session['type'] = 'U';
        $session['created_at'] = $session['updated_at'] = Carbon::now();
        if (isset($session['is_ip_location'])) {
            unset($session['is_ip_location']);
        }
        return ApiSession::insert($session);
    }

    /**
     * Get user last session key data
     * @param type $device_id
     * @return type
     */
    public static function getUserLastHomeSession($user_id) {
        return static::where(function($sql) use($user_id) {
                    $sql->where("api_service", "=", "H");
                    $sql->where("type", "=", "U");
                    $sql->where("object_id", "=", $user_id);
                })->orderBy('id', 'desc')->limit(1)->first();
    }

}
