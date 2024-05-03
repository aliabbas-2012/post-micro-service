<?php

/**
 * 
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * Description of UserPost
 *
 * @author Ali
 */
class Location extends Model {

    use \App\Traits\PostMediaTrait;

    protected $fillable = [
        'id', "fs_location_id", "location_name", "address", "latitude", "longitude"
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';
    protected $table = "locations";
    protected $appends = ["map_screen"];

    public function post_locations() {
        return $this->hasMany('App\Models\PostLocation', 'location_id', 'id');
    }

    public function post() {
        return $this->hasOne('App\Models\UserPost', 'id', 'location_id');
    }

    public function searchPost() {
        return $this->morphToMany('App\Models\SearchPost', 'postable', 'id');
    }

    public function getMapScreenAttribute() {
        $url = "";
        if (!empty($this->attributes['file'])) {
            $url = $this->getPostMedia("location", $this->attributes['file']);
        }
        return $url;
    }

    /**
     * Get location by multiple column type
     * @param type $col
     * @param type $val
     * @return type
     */
    public static function getLocationByCol($col = 'id', $val = '0') {
        return Location::where($col, '=', $val)->first();
    }

    /**
     * Fetch attributes for cached
     * @param type $source_keys
     * @return type
     */
    public static function getLocationsForCached($source_keys = []) {
        $hmArray = $response = [];
        $redis = Redis::connection();
        $super_key = config("general.redis_keys.api");
        if ($rows = array_filter($redis->hmget("api-attributes", $source_keys))) {
            foreach ($rows as $key => $row) {
                $row = json_decode($row, true);
                $row["source_type"] = "google";
                $row["source_id"] = $row["fs_location_id"];
                $response[$row['source_key']] = $row;
            }
            $source_keys = array_diff(array_keys($response), $source_keys);
        }
        if (!empty($source_keys)) {
            $columns = ["id", "source_key", "location_type", "item_type_number",
                "fs_location_id", "location_name", "address", "latitude", "longitude", "rating", "map_image",
                "400_600", "600_300", "created_at", "updated_at", "caption",
                "is_processed", "thumbnail", "bg_color",
                "gallery", "tags", "width", "height",
                "thumb_status", "is_available", \DB::raw("0 as fayvs,'A' as content_type,fs_location_id as source_id,'google' as source_type")];
            $query = Location::select($columns)->whereIn("source_key", $source_keys);
            if ($locations = $query->get()->toArray()) {
                foreach ($locations as $key => $location) {
                    $hmArray[$location['source_key']] = json_encode($location);
                    $response[$location['source_key']] = $location;
                }
                $redis->hmset($super_key, $hmArray);
                $redis->expire($super_key, 172800); //2 Days
            }
        }
        return $response;
    }

    /**
     * Fetch attributes for cached
     * @param type $ids
     * @return type
     */
    public static function getPostPlaceFromCached($ids = []) {
        $response = [];
        $redis = Redis::connection();
        $super_key = config("general.redis_keys.locations");
        $hmArr = [];
        try {

            if ($hmGet = $redis->hmGET($super_key, $ids)) {
                foreach ($hmGet as $model) {
                    if ($row = array_filter(json_decode($model, true))) {
                        $response[$row['id']] = $row;
                    }
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        } finally {

            if (empty($response)) {
                $columns = [
                    "id", "fs_location_id", "location_name", "tags",
                    "latitude", "longitude", "latitude", "longitude",
                    \DB::raw("map_image as map_screen"), "map_image",
                ];
                $query = Location::select($columns)->whereIn("id", $ids);
                if ($location = $query->get()) {
                    foreach ($location as $location) {
                        $row = $location->toArray();
                        $response[$row['id']] = $row;
                        $hmArr[$row['id']] = json_encode($row);
                    }

                    if (!empty($hmArr)) {
                        $redis->hmSet($super_key, $hmArr);
                        $redis->expire($super_key, 172800); //2 Days
                    }
                }
            }
        }

        return $response;
    }

}
