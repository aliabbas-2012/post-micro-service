<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;

class BoxBookmark extends Model {

    protected $table = 'user_bookmark';
    protected $fillable = [
        'id', 'user_id', 'relation_id', 'relation_type',
        'created_at', 'updated_at', 'status', 'owner_id'
    ];

    /**
     * 
     * @param type $user_id
     * @param type $relation_type
     * @param type $relation_ids
     * @return type
     */
    public static function getBookMarkedStatuses($user_id, $relation_type = "P", $relation_ids = []) {
        $hmArray = $response = [];
        Redis::select(config("database.redis.user_data.database"));
        $super_key = "bookmarks_{$user_id}_{$relation_type}";

        if ($rows = array_filter(Redis::hmget($super_key, $relation_ids))) {
            foreach ($rows as $key => $row) {
                $row = json_decode($row, true);
                $response[$row['relation_id']] = $row;
            }
            $relation_ids = array_diff(array_keys($response), $relation_ids);
        }


        if (!empty($relation_ids)) {

            $query = self::select('id', 'user_id', 'relation_id', 'relation_type', "status", "owner_id")
                    ->whereIn("relation_id", $relation_ids)
                    ->where("relation_type", "=", $relation_type)
                    ->where("user_id", "=", $user_id)
                    ->where("status", "=", "A");

            if ($bookmarks = $query->get()) {

                foreach ($bookmarks as $bookmark) {

                    $attr = $bookmark->toArray();

                    $hmArray[$attr['relation_id']] = json_encode($attr);
                    $response[$attr['relation_id']] = $attr;
                }

                if (!empty($hmArray)) {
                    Redis::hmset($super_key, $hmArray);
                    Redis::expire($super_key, 172800); //2 Days
                }
            }
        }
        return $response;
    }

}
