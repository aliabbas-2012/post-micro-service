<?php

namespace App\Traits;

/**
 * Description of ClearCache
 * @author ali
 */
use App\Helpers\CacheManager;

Trait ClearCache {

    /**
     * 
     * @param type $param
     */
    public function clearPlaceCache($param) {
        /**
         * To avoid exception we stored in array in case of finding param is no array 
         */
        $user_ids = is_array($param) ? $param : [$param];

        foreach ($user_ids as $user_id) {
            CacheManager::getInstance(config("general.mem_cache_keys.place-location-cache"), $user_id)->destroyData();
            CacheManager::getInstance(config("general.mem_cache_keys.place-cache"), $user_id)->destroyData();
        }
    }

    /**
     * Clear user you activities cache
     * @param type $param
     * @return boolean
     */
    public function clearYouCache($param) {
        $user_ids = is_array($param) ? $param : [$param];
        foreach ($user_ids as $key => $user_id) {
            \Cache::forget(config("general.mem_cache_keys.you-activities") . $user_id);
//            CacheManager::getInstance(config("general.mem_cache_keys.you-activities"), $user_id)->destroyData();
        }
        return true;
    }

    /**
     * Clear login user cache
     * @param type $param
     * @return boolean
     */
    public function clearLoginUserCache($param) {
        $user_ids = is_array($param) ? $param : [$param];
        foreach ($user_ids as $key => $user_id) {
            \Cache::forget(config("general.mem_cache_keys.login") . $user_id);
//            CacheManager::getInstance(config("general.mem_cache_keys.login"), $user_id)->destroyData();
        }
        return true;
    }

}
