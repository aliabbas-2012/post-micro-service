<?php

namespace App\Traits;

use GuzzleHttp\Client;
use App\Models\User;

/**
 * Description of CacheTrait
 * @author Rizwan Saleem
 */
Trait CacheTrait {

 

    /**
     * Check data exists in cahche
     * @param type $key
     * @return boolean
     */
    public function checkDataExistsInCache($key) {
        if (\Cache::has($key)) {
            return true;
        }
        return false;
    }

    /**
     * Get data from chache using KEY
     * @param type $key
     * @return type
     */
    public function getCahchedData($key) {
        if ($result = \Cache::get($key)) {
            return $result;
        }
        return [];
    }

    /**
     * Store data into cache
     * @param type $key
     * @param type $data
     * @param type $time
     * @return boolean
     */
    public function storeDataInCache($key, $data, $time = 100) {
        \Cache::put($key, $data, $time);
        return true;
    }

    /**
     * Clear cache by key
     * @param type $key
     * @return boolean
     */
    public function clearCacheByKey($key) {
        \Cache::forget($key);
        return true;
    }

    /**
     * Clear users cache by key
     * @param type $key
     * @param type $users
     * @return boolean
     */
    public function clearUsersCacheByKey($key, $users) {
        if (!empty($users)) {
            foreach ($users as $user) {
                \Cache::forget($key . $user);
            }
        }
        return true;
    }

}
