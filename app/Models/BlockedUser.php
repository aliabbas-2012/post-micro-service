<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of BlockUser
 *
 * @author farazirfan
 */
class BlockedUser extends Model {

    protected $table = 'blocked_users';
    protected $primaryKey = 'id';
    protected $guarded = array();

    /**
     *
     * @param type $user_id
     *      $user_id = can be current login id 
     * @param type $blocked_user_id
     *      $blocked_user_id = profile_id
     * @return type
     */
    public static function checkbBlockUser($user_id, $blocked_user_id) {

        $query = BlockedUser::where(function ($sql) use ($user_id, $blocked_user_id) {
                    $sql->where('user_id', '=', $user_id);
                    $sql->where('blocked_user_id', '=', $blocked_user_id);
                })->orWhere(function ($sql) use ($user_id, $blocked_user_id) {
            $sql->where('user_id', '=', $blocked_user_id);
            $sql->where('blocked_user_id', '=', $user_id);
        });
        if ($query->exists()) {
            return true;
        }
        return false;
    }

    /**
     * get both blocked user ids by user
     * @param type $user_id
     * @return type
     */
    public static function getBlockedUsersIDs($user_id) {
        $users = BlockedUser::where(function ($sql) use ($user_id) {
                    $sql->where('user_id', '=', $user_id);
                    $sql->orWhere('blocked_user_id', '=', $user_id);
                })->get();
        if (!empty($users)) {
            $ids = array_merge(array_column($users->toArray(), 'user_id'), array_column($users->toArray(), 'blocked_user_id'));
            $blockIds = self::putBlockListInCache($user_id, array_values(array_unique($ids)));
            return $blockIds;
        }
        return [];
    }

    /**
     * Check user block list in cache
     * @param type $user_id
     * @param type $list
     * @return type
     */
    public static function cachedUserBlockList($user_id, $list = []) {
        $cacheKey = config('general.mem_cache_keys.block-user-list') . $user_id;
        if ($data = \Cache::get($cacheKey)) {
            return $data;
        } else {
            $blockIds = self::getBlockedUsersIDs($user_id);
            return $blockIds;
        }
    }

    /**
     * Put block list in cache
     * @param type $user_id
     * @param type $blockIds
     * @return boolean
     */
    public static function putBlockListInCache($user_id = 0, $blockIds = []) {
        $cacheKey = config('general.mem_cache_keys.block-user-list') . $user_id;
        foreach ($blockIds as $key => $id) {
            if ($user_id == $id) {
                unset($blockIds[$key]);
            }
        }
        \Cache::put($cacheKey, $blockIds, 10080); // 7 days
        return $blockIds;
    }

}
