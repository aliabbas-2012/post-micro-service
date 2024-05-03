<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaPost extends Model {

    /**
     * Model primary key/unique key
     * @var type 
     */
    protected $primarykey = 'id';

    /**
     * Model tables
     * @var type 
     */
    protected $table = 'media_posts';

    /**
     * Fetch default media
     * @var type 
     */
    protected $with = ["postMedia"];

    /**
     * Fillable array
     * @var type 
     */
    protected $fillable = [
        'id', 'post_type_id', 'user_id', 'archive', 'created_at', 'updated_at'
    ];

//    protected $appends = ["require_full"];

    /**
     * Morph relation
     * @return type
     */
    public function post() {
        return $this->morphToMany('App\Models\UserPost', 'postable', 'id');
    }

    public function getRequireFullAttribute() {
        return $this->attributes;
    }

    /**
     * Media
     * @return type
     */
    public function postMedia() {
        return $this->hasMany('App\Models\PostMedia', 'user_post_id', 'id')->orderBy("id", "ASC");
    }

    public function latestPostMedia() {
        return $this->hasOne('App\Models\PostMedia', 'user_post_id', 'id')->orderBy("id", "ASC");
    }

    public function postLocation() {
        return $this->hasOne('App\Models\PostLocation', 'user_post_id', 'id');
    }

    public function postBoxesPivot() {
        return $this->belongsToMany('App\Models\Box', 'user_posts_boxes', 'user_posts_id', 'box_id');
    }

    public function postTagsPivot() {
        return $this->belongsToMany('App\Models\User', 'post_tag_users', 'user_post_id', 'user_id');
    }

    public function postComments() {
        return $this->hasMany('App\Models\PostComment', 'user_post_id', 'id')->orderBy('id', 'DESC');
    }

    public function postLikes() {
        return $this->hasMany('App\Models\Like', 'liked_to', 'id')->orderBy('id', 'DESC');
    }

    /**
     * Create new location post
     * @param type $inputs
     * @return \App\Model\LocationPost
     */
    public static function createPost($inputs) {
        $post = new MediaPost($inputs);
        if ($post->save()) {
            return $post;
        }
        return [];
    }

}
