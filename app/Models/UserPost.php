<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;

/**
 * Description of UserPost
 *
 * @author rizwan
 */
class UserPost extends Model {

    protected $fillable = [
        'id', "text_content", "user_id", "post_type_id", "web_url",
        "local_db_path", "uid", "client_ip_latitude", "client_ip_longitude",
        "client_ip_address", 'postable_type', 'archive',
        "location_id", "deleted_at"
    ];

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
    protected $table = 'user_posts';
    protected $appends = ['created_at_app'];

    /**
     * Morph relation
     * @return type
     */
    public function postable() {
        return $this->morphTo();
    }

    public function postBoxesPivot() {
        return $this->belongsToMany('App\Models\Box', 'user_posts_boxes', 'user_posts_id', 'box_id');
    }

    public function place() {
        return $this->hasOne('App\Models\Location', 'id', 'location_id');
    }

    /**
     * Media
     * @return type
     */
    public function postMedia() {
        return $this->hasMany('App\Models\PostMedia', 'user_post_id', 'id')->orderBy("id", "ASC");
    }

    /**
     * Media
     * @return type
     */
    public function postTags() {
        return $this->hasMany('App\Models\PostTagUser', 'user_post_id', 'id');
    }

    /**
     * Relation with user
     * @return type
     */
    public function user() {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function postTotalLikes() {
        return $this->hasOne('App\Models\Like', 'liked_to', 'id')
                        ->selectRaw('liked_to,count(*) as total_likes ')
                        ->groupBy('liked_to');
    }

    public function postTotalComments() {
        return $this->hasOne('App\Models\PostComment', 'user_post_id', 'id')->where(function ($sql) {
//                            $sql->where('archive', '=', false);
                        })->selectRaw('user_post_id,count(*)  as total_comments ')
                        ->groupBy('user_post_id');
    }

    public function postLikesByUser() {
        return $this->hasOne('App\Models\Like', 'liked_to', 'id');
    }

//    public function postLikesByUser() {
//        return $this->hasOne('App\Models\Like', 'liked_to', 'id');
//    }

    /**
     * get single post by id
     * @param type $id
     * @return type
     */
    public static function getPostById($id) {
        $columns = [\DB::raw("*"), \DB::raw("id as postable_id")];
        return static::select($columns)->with('postable')->where('id', '=', $id)->first();
    }

    /**
     * date formate for application
     * @return type
     */
    public function getCreatedAtAppAttribute() {
        if (isset($this->attributes['created_at'])) {
            return !empty($this->created_at) ? $this->created_at->format("Y-m-d\TH:i:s\Z") : "";
        }
    }

    /**
     * Human date format for app
     * @param type $timezone
     * @return type
     */
    public function getHumanCreatedAt($timezone) {
        if (!empty($this->created_at)) {
            $created_at = $this->created_at->setTimezone($timezone)->diffForHumans();
            $created_at = str_replace([' seconds', ' second'], ' sec', $created_at);
            $created_at = str_replace([' minutes', ' minute'], ' min', $created_at);
            $created_at = str_replace(['1 day ago'], ' yesterday', $created_at);
            return $created_at;
        }
        return "";
    }

    public static function getHomePosts($user_id) {
        $columns = [\DB::raw("*"), \DB::raw("id as postable_id")];
        return UserPost::select($columns)->where('user_id', '=', $user_id)
                        ->with('postable')->orderBy('id', 'desc')->limit(10);
    }

    /**
     * Get post totle likes/comments/views
     * @param type $ids
     * @return type
     */
    public static function getPostsTotalLikesComments($ids = [], $user_id = 0) {
        $response = [];
        if (!empty($ids)) {
            $posts = UserPost::select("id")->where(function ($sql) use ($ids) {
                        $sql->whereIn('id', $ids);
                        $sql->where('archive', "=", false);
                    })->with(['postTotalLikes', 'postTotalComments', "postLikesByUser" => function ($sql) use ($user_id) {
                            $sql->where("liked_by", "=", $user_id);
                            $sql->where("like_type_number", "=", 1);
                        }])->get();
            if (!$posts->isEmpty()) {
                foreach ($posts->toArray() as $key => $post) {
                    $response[$post['id']] = [
                        'likes' => isset($post['post_total_likes']['total_likes']) ? $post['post_total_likes']['total_likes'] : 0,
                        'comments' => isset($post['post_total_comments']['total_comments']) ? $post['post_total_comments']['total_comments'] : 0,
                        'views' => 0, "is_liked" => !empty($post['post_likes_by_user']) ? true : false,
                        "reaction_id" => !empty($post['post_likes_by_user']) ? (int) $post['post_likes_by_user']['reaction_id'] : 0,
                    ];
                }
            }
        }
        return $response;
    }

    /**
     * Get post basic info by id
     * @param type $post_id
     * @return type
     */
    public static function getBaseInfoById($post_id) {
        $response = $hmarr = [];
        $parent_key = "comment-posts";
        Redis::select(config('database.redis.content_data.database'));
        if ($data = Redis::hget($parent_key, $post_id)) {
            \Log::info("--Post founded from cache --> {$post_id}");
            $response = json_decode($data, true);
        } else {
            $post = static::select(\DB::raw("id,user_id,status,archive"))
                            ->where("id", "=", $post_id)->first()->toArray();
            if (!empty($post)) {
                Redis::hset($parent_key, $post_id, json_encode($post));
                Redis::expire($parent_key, 86400);
                $response = $post;
            }
        }
        return $response;
    }

}
