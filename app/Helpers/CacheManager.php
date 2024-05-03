<?php

namespace App\Helpers;

/**
 * MemCache Manager
 *
 * @author ali
 */
class CacheManager {

    public static $key, $user_id;
    private static $object;

    static function getInstance($key, $user_id) {
        if (null == self::$object) {
            self::$object = new CacheManager($key, $user_id);
        }
        self::makeKey($key, $user_id);
        return self::$object;
    }

    static function makeKey($key, $user_id) {
        self::$key = $key . "-" . $user_id;
    }

    private function __construct() {
        
    }

    public function getData() {
        if (\Cache::has(self::$key)) {
            return \Cache::get(self::$key);
        }
        return [];
    }

    public function storeData($data, $time = 10) {

        \Cache::put(self::$key, $data, $time);
    }

    public function destroyData() {
        \Cache::forget(self::$key);
    }

}
