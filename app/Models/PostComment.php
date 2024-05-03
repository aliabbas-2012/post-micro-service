<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;

/**
 * Description of PostComment
 *
 * @author rizwan
 */
class PostComment extends Model {

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
    protected $table = 'post_comments';
    public $timestamps = false;
    protected $fillable = ['id', 'user_id', 'user_post_id', 'post_owner', 'comment', 'created_at', 'deleted_at', 'archive', 'client_ip_address',
        'lat', 'lon', 'geo_location', 'is_ip_location', 'country_id', 'service'];
    protected $hidden = ['deleted_at', 'archive'];

    /**
     * Relation with user
     * @return type
     */
    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id', 'id')
                        ->select(array('id', 'uid', 'username', 'full_name', 'picture', 'bucket', 'is_live', 'is_verified'));
    }

    public function post() {
        return $this->belongsTo('App\Models\UserPost', 'user_post_id', 'id')
                        ->select(array('id', 'user_id', 'text_content', 'client_ip_address', 'client_ip_latitude', 'client_ip_longitude', 'post_type_id'));
    }

    public function comment_count($id) {
        return static::where('user_post_id', '=', $id)->get()->count();
    }

    /**
     * comment can have more than one mention users or not any
     * @return type
     */
    public function commentMention() {
        return $this->hasMany('App\Models\CommentMentionUser', 'post_comments_id', 'id');
    }

    /**
     * 
     * @param type $post_ids
     */
    public static function getTopPostComments($user_id, $post_ids = []) {
        Redis::select(config("database.redis.user_data.database"));
        $to_diff = [];

        $super_key = config("general.redis_keys.top_coments");
        $hmArr = [];
        $comment_candidates = [];
        $group = [];

        if (!empty($post_ids)) {
            if ($mGet = array_filter(Redis::hMGet($super_key, $post_ids))) {

                foreach ($mGet as $post_id => $topComments) {
                    $topComments = json_decode($topComments, true);

                    foreach ($topComments as $comment) {
                        $to_diff[] = $comment["user_post_id"];
                        $group[$comment["user_post_id"]][] = $comment;
                        $comment_candidates[$comment["user_id"]] = $comment["user_id"];
                    }
                }
            }
        }

        $post_ids_where = array_diff($post_ids, $to_diff);

        if (!empty($post_ids_where)) {
            \Log::info("--- post top comments SQL ----");
            $columns = ["id", "user_id", "user_post_id", "comment", "created_at"];
            $top_comments = self::select($columns)->whereIn("user_post_id", $post_ids_where)
                            ->orderBy("id", "DESC")
                            ->limit(3)->get()->toArray();

            foreach ($top_comments as $comment) {
                $comment_candidates[$comment["user_id"]] = $comment["user_id"];
                $group[$comment["user_post_id"]][] = $comment;
            }
            foreach ($group as $post_id => $comments) {
                $hmArr[$post_id] = json_encode($comments);
            }

            if (!empty($hmArr)) {

                Redis::hmset($super_key, $hmArr);
                Redis::expire($super_key, 172800); //2 Days
            }
        }


        return [$group, $comment_candidates];
    }

    /**
     * 
     * @param type $post_ids
     */
    public static function getCommentCount($post_ids) {
        Redis::select(config("database.redis.content_data.database"));
        $to_diff = [];
        $comment_count_arr = [];
        $super_key = config("general.redis_keys.comment_count");
        $hmArr = [];
        if (!empty($post_ids)) {

            if ($comments = array_filter(Redis::hMGet($super_key, $post_ids))) {

                foreach ($comments as $comment) {
                    $comment = json_decode($comment, true);
                    $to_diff[] = $comment["user_post_id"];
                    $comment_count_arr[$comment["user_post_id"]] = $comment;
                }
            }
        }

        $post_ids_where = array_diff($post_ids, $to_diff);

        if (!empty($post_ids_where)) {
            \Log::info("--- post comment counts SQL ----");
            $comments = PostComment::select(\DB::raw('count(*) as total, user_post_id'))
                            ->whereIn("user_post_id", $post_ids_where)
                            ->groupBy("user_post_id")
                            ->get()->toArray();
            foreach ($comments as $comment) {
                $comment_count_arr[$comment["user_post_id"]] = $comment;
                $hmArr[$comment["user_post_id"]] = json_encode($comment);
            }
            if (!empty($hmArr)) {
                Redis::hmset($super_key, $hmArr);
                Redis::expire($super_key, 172800); //2 Days
            }
        }
        return $comment_count_arr;
    }

}
