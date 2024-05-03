<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * Description of Box
 *
 * @author farazirfan
 */
class Box extends Model {

    protected $primarykey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'box';
    protected $fillable = [
        'user_id',
        'name',
        'show_public',
        'show_followers',
        'show_me'
    ];

    public function user() {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function boxLastPost() {
        return $this->hasOne("App\Models\PostBox", "box_id", "box_id")
                        ->select(array("user_posts_id", "box_id"))->orderBy("id", "desc");
    }

    /**
     * get box by id
     * @param type $id
     * @return type
     */
    public static function getBoxById($id, $user_id = 0) {
        $result = static::where('id', '=', $id)
                        ->whereHas('user', function ($q) {
                            
                        })->with(['user' => function ($q) use ($user_id) {
                        $q->with(['isFollowed' => function ($sql) use ($user_id) {
                                $sql->where('follower_id', '=', $user_id);
                                $sql->whereIn('status', ['A', 'P']);
                            }]);
                        $q->select('id', 'uid', 'is_live', 'username', 'bucket', 'picture', 'full_name');
                    }])->first();
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * 
     * @param type $box_ids
     * @return type
     */
    public static function getCachedBoxes($box_ids) {
        $arr = [];
        $super_key = config("general.redis_keys.boxes");
        Redis::select(config("database.redis.user_data.database"));
        if (!empty($box_ids)) {
            if ($mGet = array_filter(Redis::hMGet($super_key, $box_ids))) {
                foreach ($mGet as $data) {
                    $box = json_decode($data, true);
                    $arr[$box["id"]] = $box;
                }
            }
        }

        if ($box_ids = array_diff($box_ids, array_keys($arr))) {
            $columns = ["id", "name", "status"];
            $boxes = self::select($columns)->whereIn("id", $box_ids)->orderBy("id", "DESC")->get()->toArray();

            foreach ($boxes as $box) {
                $arr[$box["id"]] = $box;
            }

            if (!empty($arr)) {
                foreach ($arr as $box_id => $box) {
                    $hmArr[$box_id] = json_encode($box);
                }
            }
            if (!empty($hmArr)) {
                Redis::hmset($super_key, $hmArr);
                Redis::expire($super_key, 172800); //2 Days
            }
        }

        return $arr;
    }

}
