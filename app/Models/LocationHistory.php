<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of MLUser
 *
 * @author rizwan
 */
class LocationHistory extends Model {

    protected $connection = 'stats_db';
    protected $table = 'location_history';
    protected $fillable = ["id", "object_id", "type", "service",
        "lat", "lon", "client_ip_address", "priority"];

    /**
     * Save new location history
     * @param type $inputs
     * @return \App\Models\LocationHistory
     */
    public static function saveLocation($inputs, $object) {
        $inputs["type"] = "U";
        $inputs["object_id"] = $object->id;
        $location = new LocationHistory($inputs);
        if ($location->save()) {
            return $location;
        }
        return [];
    }

}
