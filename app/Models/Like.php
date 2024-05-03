<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\PostRelationHelper;
use App\Models\Friend;
use Illuminate\Support\Facades\Redis;

class Like extends Model {

    //
    protected $primarykey = 'id';
    protected $table = 'likes';
    public $timestamps = false;
    protected $fillable = ['liked_to', 'liked_by', 'liked_type', 'created_at'];

    public function user() {
        return $this->hasOne('App\Models\User', 'id', 'liked_by');
    }

    public function post() {
        return $this->hasOne('App\Models\UserPost', 'id', 'liked_to');
    }

    public function postBox() {
        return $this->belongsTo('App\Models\PostBox', 'user_posts_id', 'liked_to');
    }

    /**
     * Get post count likes
     * @param type $post_id
     * @return type
     */
    public static function countPostLikes($post_id) {
        $result = Like::where('liked_to', '=', $post_id)->where('like_type_number', '=', 1)->count();
        return ($result > 0) ? $result : 0;
    }

    public static function getLikePosts($user_id) {
        $query = self::getLikePostQuery($user_id);
        $query->where("liked_by", "=", $user_id);
        $query->limit(200);
        return $query;
    }

    /**
     * 
     * @param type $user_id
     * @param type $post_types
     * @return type
     */
    private static function getLikePostQuery() {
        $query = Like::distinct("liked_to")->select("liked_to")
                ->whereHas('post', function ($sql) {
                    $sql->where("archive", "=", false);
                })
                ->with(PostRelationHelper::postCommonRelations());

        $query->orderBy("id", "DESC");
        return $query;
    }

    /**
     * 
     * @param type $post_ids
     */
    public static function getPostDistinctReactions($post_ids = [], $visitor_id = 0) {

        Redis::select(config("database.redis.user_data.database"));
        $group = [];
        $to_diff = [];
        $reactive_candidates = [];
        $super_key = config("general.redis_keys.reactions");
        if (!empty($post_ids)) {
            if ($mGet = array_filter(Redis::hMGet($super_key, $post_ids))) {
                foreach ($mGet as $post_id => $data) {
                    $reactions = json_decode($data, true);
                    foreach ($reactions as $reaction) {
                        $group[$reaction["liked_to"]][] = $reaction;
                        $to_diff[] = $reaction["liked_to"];

                        $reactive_candidates[$reaction["liked_by"]] = $reaction["liked_by"];
                    }
                }
                $to_diff = array_unique($to_diff);
            }
        }
        $post_ids_where = array_diff($post_ids, $to_diff);
        if (!empty($post_ids_where)) {
            \Log::info("--- Like reactions query SQL --");
            $reactions = Like::select("id", "liked_by", "reaction_id", "liked_to", "created_at")
                            ->whereIn("liked_to", $post_ids_where)
//                            ->groupBy("reaction_id", "liked_by")
                            ->orderBy("created_at", "ASC")
                            ->get()->toArray();
            foreach ($reactions as $reaction) {
                $group[$reaction["liked_to"]][] = $reaction;
                $reactive_candidates[$reaction["liked_by"]] = $reaction["liked_by"];
            }

            foreach ($group as $post_id => $reactions) {
                $hmArr[$post_id] = json_encode($reactions);
            }



            if (!empty($hmArr)) {
                Redis::hmset($super_key, $hmArr);
                Redis::expire($super_key, 172800); //2 Days
            }
        }
        return self::excludeSelfReaction($group, $reactive_candidates, $visitor_id);
    }

    /**
     * 
     * @param type $user_id
     * @param type $post_ids
     */
    public static function getPostIsLikedReactions($user_id, $post_ids = []) {

        Redis::select(config("database.redis.user_data.database"));

        $to_diff = [];
        $is_liked_arr = [];
        $super_key = config("general.redis_keys.is_liked") . "{$user_id}";

        $hmArr = [];
        if (!empty($post_ids)) {


            if ($reactions = array_filter(Redis::hMGet($super_key, $post_ids))) {

                foreach ($reactions as $reaction) {
                    $reaction = json_decode($reaction, true);
                    $to_diff[] = $reaction["liked_to"];
                    $is_liked_arr[$reaction["liked_by"]][$reaction["liked_to"]] = $reaction;
                }
            }
        }
        $post_ids_where = array_diff($post_ids, $to_diff);

        if (!empty($post_ids_where)) {
            \Log::info("--- post id liked SQL ---");
            $reactions = Like::select("id", "liked_by", "reaction_id", "liked_to", "created_at")
                            ->whereIn("liked_to", $post_ids_where)
                            ->where("liked_by", $user_id)
                            ->get()->toArray();
            foreach ($reactions as $reaction) {
                $is_liked_arr[$reaction["liked_by"]][$reaction["liked_to"]] = $reaction;
                $hmArr[$reaction["liked_to"]] = json_encode($reaction);
            }
            if (!empty($hmArr)) {
                Redis::hmset($super_key, $hmArr);
                Redis::expire($super_key, 172800); //2 Days
            }
        }



        return $is_liked_arr;
    }

    /**
     * 
     * @param type $post_ids
     */
    public static function getLikeCount($post_ids) {
        Redis::select(config("database.redis.content_data.database"));

        $to_diff = [];
        $liked_count_arr = [];
        $super_key = config("general.redis_keys.like_count");
        $hmArr = [];
        if (!empty($post_ids)) {

            if ($likes = array_filter(Redis::hMGet($super_key, $post_ids))) {

                foreach ($likes as $like) {
                    $like = json_decode($like, true);
                    $to_diff[] = $like["liked_to"];
                    $liked_count_arr[$like["liked_to"]] = $like;
                }
            }
        }

        $post_ids_where = array_diff($post_ids, $to_diff);

        if (!empty($post_ids_where)) {
            \Log::info("--- post likes count SQL ---");
            $likes = Like::select(\DB::raw('count(*) as total, liked_to'))
                            ->whereIn("liked_to", $post_ids_where)
                            ->groupBy("liked_to")
                            ->get()->toArray();
            foreach ($likes as $like) {
                $liked_count_arr[$like["liked_to"]] = $like;
                $hmArr[$like["liked_to"]] = json_encode($like);
            }

            if (!empty($hmArr)) {
                Redis::hmset($super_key, $hmArr);
                Redis::expire($super_key, 172800); //2 Days
            }
        }

        return $liked_count_arr;
    }

    /**
     * Exlude self reactions from list
     * @param type $group
     * @param type $reactive_candidates
     * @param type $visitor_id
     * @return type
     */
    private static function excludeSelfReaction($group, $reactive_candidates, $visitor_id = 0) {
        $post_reactions = [];
        if ($visitor_id > 0) {
            if (isset($reactive_candidates[$visitor_id])) {
                unset($reactive_candidates[$visitor_id]);
            }
            foreach ($group as $post_id => $reactions) {
                $reaction_group = [];
                foreach ($reactions as $key => $reaction) {
                    if ($reaction["liked_by"] == $visitor_id) {
                        continue;
                    }
                    $reaction_group[$reaction['reaction_id']] = $reaction;
                }
                $post_reactions[$post_id] = array_values($reaction_group);
            }
        } else {
            $post_reactions = $group;
        }
        return [$post_reactions, $reactive_candidates];
    }

}
