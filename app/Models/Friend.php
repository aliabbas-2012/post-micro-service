<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\ElasticSearchHelper;
use Illuminate\Support\Facades\Redis;

class Friend extends Model {

    protected $table = 'friends';
    protected $primaryKey = 'id';
    protected $guarded = array();
    public $timestamps = false;

    /**
     * Check whether both persons are friends or not or following each others or one of them
     * 
     * @param type $login_user
     * @param type $other_user
     * @return type
     */
    public static function checkUserFollowingFollower($login_user, $other_user, $following_me = true) {
        $query = Friend::where('status', '=', 'A');

        //if other user following current users then this will execute
        if ($following_me) {
            $query->where(function ($q) use ($login_user, $other_user) {
                $q->where(function ($sql) use ($login_user, $other_user) {
                    $sql->where('following_id', '=', $login_user);
                    $sql->where('follower_id', '=', $other_user);
                });
            })->orWhere(function ($sql) use ($login_user, $other_user) {
                $sql->where('following_id', '=', $other_user);
                $sql->where('follower_id', '=', $login_user);
            });
        } else {
            $query->where(function ($sql) use ($login_user, $other_user) {
                $sql->where('following_id', '=', $other_user);
                $sql->where('follower_id', '=', $login_user);
            });
        }

        return $query->exists();
    }

    /**
     * 
     * @param type $user_id
     * @param type $viaEs
     * @return type
     */
    public static function getFriendCount($user_id, $viaEs = false) {

        if (!$viaEs) {
            return Friend::where("follower_id", "=", $user_id)->where("status", "=", "A")->count();
        }
        $body = [
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "type" => "followings"
                            ]
                        ],
                        [
                            "term" => [
                                "object_id" => "u-" . $user_id
                            ]
                        ],
                        [
                            "term" => [
                                "status" => "A"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $es = new ElasticSearchHelper();
        return $es->getCountAgainstQuery($body);
    }

    /**
     * 
     * @param type $user_id
     * @return type
     */
    public static function getFollowingList($user_id) {
        $query = self::select("following_id")->where("follower_id", "=", $user_id)->where("status", "=", "A");
        return $query->pluck('following_id')->toArray();
    }

    /**
     * validate user is followed or not
     * @param type $login_id
     * @param type $other_user
     * @return boolean
     */
    public static function isFolloweduser($login_id, $other_user) {
        $result = static::where(function ($q) use ($login_id, $other_user) {
                    $q->where('follower_id', '=', $login_id);
                    $q->where('following_id', '=', $other_user);
                    $q->where('status', '=', 'A');
                });
        if ($result->exists()) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param type $user_id
     * @param type $candidate_ids
    */
    public static function getListFromCacheAndDB($user_id, $candidate_ids = []) {

        $redis = Redis::connection('friend_list');

        $data = self::filterDataFromCache($user_id, $candidate_ids);
        $filtered_candidate_ids = array_diff($candidate_ids, array_keys($data));
        $hmArr = [];

        if (!empty($filtered_candidate_ids)) {
            $followings = Friend::where("follower_id", "=", $user_id)
                    ->whereIn("following_id", $filtered_candidate_ids)->get()
                    ->toArray();
            foreach ($followings as $folllowing) {
                $hmArr["followers"]["{$folllowing["follower_id"]}-{$folllowing["following_id"]}"] = json_encode($folllowing);
                $hmArr["followings"]["{$folllowing["following_id"]}-{$folllowing["follower_id"]}"] = json_encode($folllowing);

                $data[$folllowing["following_id"]] = $folllowing["status"];
            }
        }
        //processing again to minimize load 
        $filtered_candidate_ids_v2 = array_diff($filtered_candidate_ids, array_keys($data));
        foreach ($filtered_candidate_ids_v2 as $candidate_id) {
            $model = ["follower_id" => $user_id, "following_id" => $candidate_id, "status" => "U"];
            $hmArr["followers"]["{$user_id}-{$candidate_id}"] = json_encode($model);
            $hmArr["followings"]["{$candidate_id}-{$user_id}"] = json_encode($model);

            $data[$candidate_id] = "U";
        }

        if (!empty($hmArr)) {
            $redis->hmset(config("general.redis_keys.followers"), $hmArr["followers"]);
            $redis->hmset(config("general.redis_keys.followings"), $hmArr["followings"]);
            
            $redis->expire(config("general.redis_keys.followers"), 604800); //7 Days
            $redis->expire(config("general.redis_keys.followings"), 604800); //7 Days
            
        }

        return $data;
    }

    /**
     * 
     * @param type $user_id
     * @param type $candidate_ids
     */
    private static function filterDataFromCache($user_id, $candidate_ids) {
        $hmKeys = [];
        $data = [];
        foreach ($candidate_ids as $candidate_id) {
            $hmKeys[] = "{$user_id}-{$candidate_id}";
        }
        $redis = Redis::connection('friend_list');
        $cache = array_filter($redis->hmget(config("general.redis_keys.followers"), $hmKeys));
        foreach ($cache as $data_string) {
            $folllowing = json_decode($data_string, true);
            $data[$folllowing["following_id"]] = $folllowing["status"];
        }
        return $data;
    }

}
