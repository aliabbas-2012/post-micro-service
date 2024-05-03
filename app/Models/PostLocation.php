<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use App\Models\UserPost as Post;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\PostRelationHelper;
use App\Helpers\CacheManager;

/**
 * Description of UserPost
 *
 * @author rizwan
 */
class PostLocation extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';
    protected $posts;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'post_location';
//    protected $attributes = [];
    protected $appends = ['posts', 'post_ids_arr'];

    /**
     * Relationship with user
     * @return type
     */
    public function post() {
        return $this->belongsTo('App\Models\UserPost', 'user_post_id', 'id');
    }

    public function getPostsAttribute() {
        return isset($this->attributes['posts']) ? $this->attributes['posts'] : [];
    }

    public function getPostIdsArrAttribute() {
        if (!empty($this->attributes['post_ids'])) {
            return explode(",", $this->attributes['post_ids']);
        }
        return [];
    }

    public function setPosts($post, $post_id) {
        $this->attributes['posts'][$post_id] = $post;
    }

  

    /**
     * delete locations by post ids
     * @param type $post_ids
     * @return boolean
     */
    public static function deleteLocationsByPostIds($post_ids) {
        return static::whereIn('user_post_id', $post_ids)->delete();
    }

}
