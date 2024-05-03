<?php

/**
 * Description of CommonTrait
 *
 * @author Rizwan Saleem
 */

namespace App\Traits;

use Illuminate\Support\Facades\Redis;

trait RedisTrait {

    /**
     * Cache user and device current location FOR 3H
     * @param type $data
     * @return boolean
     */
    public function cacheUserCurrentLocation($user_id, $device_id = "", $lat_lon) {
        $user_key = config("general.redis_keys.user_last_location") . $user_id;
        $data['location'] = $lat_lon;
        Redis::set($user_key, json_encode($data, true), 'EX', config("general.redis_keys.last_location_redis_expire_min"));
        if (!empty($device_id)) {
            $device_key = config("general.redis_keys.device_last_location") . md5($device_id);
            Redis::set($device_key, json_encode($data, true), 'EX', config("general.redis_keys.last_location_redis_expire_min"));
        }

        return true;
    }

    /**
     * Get user last cached current location
     * @param type $user_id
     * @return type
     */
    public function getUserLastLocation($user_id) {
        $user_key = config("general.redis_keys.user_last_location") . $user_id;
        return $this->getCacheArrayByKey($user_key);
    }

    /**
     * Get device last cached current location
     * @param type $device_id
     * @return type
     */
    public function getDeviceLastLocation($device_id) {
        $device_key = config("general.redis_keys.device_last_location") . md5($device_id);
        return $this->getCacheArrayByKey($device_key);
    }

    /**
     * Cache IP address
     * @param type $ip
     * @param type $data
     * @return boolean
     */
    public function cacheIpInfo($ip, $data = [], $hours = 0) {
        $key = config("general.redis_keys.client_ip") . md5($ip);
        $expire_at = $hours > 0 ? ($hours * config("general.redis_keys.redis_expire_min")) : config("general.redis_keys.redis_expire_min");
        Redis::set($key, json_encode($data, true), 'EX', $expire_at);
        return true;
    }

    /**
     * Get cache IP info
     * @param type $ip
     * @param type $data
     * @return boolean
     */
    public function getCacheIpInfo($ip) {
        $key = config("general.redis_keys.client_ip") . md5($ip);
        if ($data = Redis::get($key)) {
            return $this->decodeCacheData($data);
        }
        return [];
    }

    /**
     * Decode redis cache data
     * @param type $data
     * @return type
     */
    public function decodeCacheData($data) {
        return json_decode($data, true);
    }

    /**
     * Get cache array by KEY
     * @param type $key
     * @return type
     */
    public function getCacheArrayByKey($key) {
        if ($data = Redis::get($key)) {
            return $this->decodeCacheData($data);
        }
        return [];
    }

    /**
     * Cache array
     * @param type $key
     * @param type $data
     * @return boolean
     */
    public function cacheArrayInKey($key, $data = [], $expireHours = 0) {
        $expire_At = $expireHours > 0 ? ($expireHours * config("general.redis_keys.redis_expire_min")) : config("general.redis_keys.redis_expire_min");
        Redis::set($key, json_encode($data, true), 'EX', $expire_At);
        return true;
    }

    /**
     * Cached Home Posts top trend Posts
     * @param type $data
     */
    public function cacheTopTrendPostsHome($data = []) {
        $key = config("general.redis_keys.home_top_trending");
        Redis::set($key, json_encode($data), 'EX', config("general.redis_keys.redis_expire_min"));
    }

    /**
     * Get home cached top trend posts
     * @param type $data
     * @return type
     */
    public function getTopTrendPostsHome($data = []) {
        if ($data = Redis::get(config("general.redis_keys.home_top_trending"))) {
            return $this->decodeCacheData($data);
        }
        return [];
    }

    public function cacheUserHomeTopTrendPosts($user_id, $user_trending) {
        $key = config("general.redis_keys.user_home_top_trending") . $user_id;
        Redis::set($key, json_encode($user_trending, true), 'EX', config("general.redis_keys.redis_expire_min"));
        return true;
    }

    public function getUserHomeTopTrendPosts($user_id) {
        $key = config("general.redis_keys.user_home_top_trending") . $user_id;
        if ($data = Redis::get($key)) {
            return $this->decodeCacheData($data);
        }
        return [];
    }

    /**
     * 
    */
    public function deleteCommentCache($post_id) {
        Redis::select(config("database.redis.user_data.database"));
        $key1 = config("general.redis_keys.top_coments");
        Redis::hdel($key1,$post_id);

        Redis::select(config("database.redis.content_data.database"));
        $key2 = config("general.redis_keys.comment_count");
        Redis::hdel($key2,$post_id);
    }

}
