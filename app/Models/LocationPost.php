<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPost extends Model {

    /**
     * Model primary key/unique key
     * @var type 
     */
    protected $primarykey = 'id';

    /**
     * Model tables
     * @var type 
     */
    protected $table = 'location_posts';
    protected $with = ['postMedia'];

    /**
     * Fillable array
     * @var type 
     */
    protected $fillable = [
        'post_type_id', 'content', 'user_id', 'location_id',
        'archive', 'created_at', 'updated_at', 'id'];

    /**
     * Morph relation
     * @return type
     */
    public function post() {
        return $this->morphToMany('App\Models\UserPost', 'postable', 'id');
    }

    /**
     * Location
     * @return type
     */
    public function location() {
        return $this->hasOne('App\Models\Location', 'id', 'location_id');
    }

    /**
     * Media
     * @return type
     */
    public function postMedia() {
        return $this->hasMany('App\Models\PostMedia', 'user_post_id', 'id');
    }

    /**
     * Create new location post
     * @param type $inputs
     * @return \App\Model\LocationPost
     */
    public static function createPost($inputs) {
        $post = new LocationPost($inputs);
        if ($post->save()) {
            return $post;
        }
        return [];
    }

}
