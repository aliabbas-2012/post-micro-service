<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of UserPost
 *
 * @author rizwan
 */
class PostTagUser extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'post_tag_users';

    /**
     * Relashion with user
     * @return type
     */
    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id', 'id')
                        ->select(array('id', 'archive', 'uid', 'username', 'picture', 'bucket', 'is_live', 'is_verified'));
    }

    public function myBlockedList() {
        return $this->hasMany('App\Models\BlockedUser', 'user_id', 'user_id');
    }

    public function blockedMeList() {
        return $this->hasMany('App\Models\BlockedUser', 'blocked_user_id', 'user_id');
    }

    /**
     * get tag users by post
     * @param type $post_id
     * @param type $offset
     * @param type $limit
     * @param type $search_key
     * @return type
     */
    public static function getPostTagUsers($loging_user, $post_id, $limit, $search_key = "") {
        return static::where('user_post_id', '=', $post_id)
                        ->whereDoesntHave("myBlockedList", function($sql) use($loging_user) {
                            $sql->where("blocked_user_id", "=", $loging_user);
                        })->whereDoesntHave("blockedMeList", function($sql) use($loging_user) {
                            $sql->where("user_id", "=", $loging_user);
                        })->whereHas('user', function($sql) use($search_key) {
                            if (!empty($search_key)) {
                                $sql->where(function($sql) use($search_key) {
                                    $sql->where('username', 'like', "%$search_key%");
                                    $sql->orWhere('full_name', 'like', "%$search_key%");
                                });
                            }
                            $sql->where('archive', '=', false);
                        })->with(['user' => function($sql) use($loging_user) {
                                $sql->with(['isFollowed' => function($sql) use($loging_user) {
                                        $sql->where('follower_id', '=', $loging_user);
                                        $sql->whereIn('status', ['A', 'P']);
                                        $sql->select("id", "follower_id", "following_id", "status");
                                    }]);
                            }])
                        ->orderBy('id', 'ASC')->offset($limit['offset'])->limit($limit['limit'])->get();
    }

    /**
     * 
     * @param type $post_id
     * @return type
     */
    public static function getPostTags($post_ids, $blockIds = []) {

        $tags = static::select("user_id", "user_post_id")->whereIn('user_post_id', $post_ids)
                        ->whereNotIn("user_id", $blockIds)
                        ->orderBy("id", "DESC")->get();
        $tag_user_ids = [];
        $post_tags = [];
        foreach ($tags as $tag) {
            $post_tags[$tag->user_post_id][] = $tag->toArray();
            $tag_user_ids[$tag->user_id] = $tag->user_id;
        }
        return [$post_tags, $tag_user_ids];
    }

}
