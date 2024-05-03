<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\PostRelationHelper;
use App\Models\Location;
use App\Models\PostSearchAttribute;
use App\Models\BoxBookmark as BookMark;

/**
 * Description of UserPost
 *
 * @author rizwan
 */
class PostBox extends Model {

    protected $fillable = [
        'id', "box_id", "user_id", "post_type_id", "user_posts_id"
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
    protected $table = 'user_posts_boxes';

    /**
     * Relation with user
     * @return type
     */
    public function post() {
        return $this->belongsTo('App\Models\UserPost', 'user_posts_id', 'id');
    }

    public function box() {
        return $this->belongsTo('App\Models\Box', 'box_id', 'id');
    }

    public function postBoxes() {
        return $this->hasMany('App\Models\Box', 'id', 'box_id');
    }

    //Post owner your following
    public function following() {
        return $this->hasOne('App\Models\Friend', 'following_id', 'user_id')->where("status", "=", "A");
    }

    public function postLikesByUser() {
        return $this->hasOne('App\Models\Like', 'liked_to', 'user_posts_id');
    }

    public function postTotalLikes() {
        return $this->hasOne('App\Models\Like', 'liked_to', 'user_posts_id')
                        ->selectRaw('liked_to,count(*) as total_likes ')
                        ->groupBy('liked_to');
    }

    public function postTotalTags() {
        return $this->hasOne('App\Models\PostTagUser', 'user_post_id', 'user_posts_id')
                        ->selectRaw('user_post_id,count(*) as total_tags ')
                        ->where('is_deleted', '=', false)
                        ->groupBy('user_post_id');
    }

    public function postTotalComments() {
        return $this->hasOne('App\Models\PostComment', 'user_post_id', 'user_posts_id')->where(function ($sql) {
//                            $sql->where('archive', '=', false);
                        })->selectRaw('user_post_id,count(*)  as total_comments ')
                        ->groupBy('user_post_id');
    }

    /**
     * Limit Resutl For New Post Detail
     * @return type
     */
    public function comments() {
        return $this->hasMany('App\Models\PostComment', 'user_post_id', 'user_posts_id')->where(function ($sql) {
                    $sql->where('archive', '=', false);
                })->limit(2)->orderBy('id', 'desc');
    }

    /**
     * Last Two likes for new post detail response
     * @return type
     */
    public function likes() {
        return $this->hasMany('App\Models\Like', 'liked_to', 'user_posts_id')
                        ->limit(2)->orderBy('id', 'desc');
    }

    public function isBookmarked() {
        return $this->hasOne('App\Models\BoxBookmark', 'relation_id', 'user_posts_id')
                        ->where("relation_type", "=", "P")->where("status", "=", "A");
    }

    /**
     * Relation with user
     * @return type
     */
    public function postMedia() {
        return $this->hasOne('App\Models\PostMedia', 'user_post_id', 'user_posts_id')
                        ->select(array("user_post_id", "file", "file_type", "bucket"))
                        ->orderBy("id", 'DESC');
    }

    /**
     * Get single post by id
     * @param type $inputs
     * @return type
     */
    public static function getSinglePost($inputs = [], $user_id) {
        $query = PostBox::distinct("user_posts_id")->select("post_type_id", "user_posts_id", "archive")
                        ->where(function ($sql) use ($inputs) {
                            $sql->where('user_posts_id', '=', $inputs['post_id']);
                        })->whereHas('post', function ($sql) {
                    //
                })->with(PostRelationHelper::postCommonRelations(true));
        $query->with(PostRelationHelper::postBoxCommonRelations($user_id));
        $post = $query->first();
        return !empty($post) ? $post : [];
    }

    /**
     * 
     * @param type $user_id
     * @param type $less_than
     * @param type $greater_than
     * @param type $limit
     * @param type $post_types
     * @return type
     */
    public static function getHomePosts($user_id, $less_than = 0, $greater_than = 0, $limit = 40, $post_types = []) {

        $query = self::getHomeQuery($user_id, $post_types);

        $totalFollowers = Friend::getFriendCount($user_id, true);
        if ($totalFollowers > 0) {
            $query = self::getHomeSubQuery($query, $totalFollowers, $user_id);
            $query->whereHas("box", function ($sql) use ($user_id) {
                $sql->where(function ($query) use ($user_id) {
                    $query->where(function ($query) use ($user_id) {
                        $query->where("user_id", "=", $user_id)->whereIn("status", ["M", "A", "F"]);
                    })->orWhere(function ($query) use ($user_id) {
                        $query->where("user_id", "<>", $user_id)->whereIn("status", ["A", "F"]);
                    });
                });
            });
        } else {
            $query->where("user_id", "=", $user_id);
        }

        if ($less_than > 0) {

            $query->where("user_posts_id", "<", $less_than);
        }
        if ($greater_than > 0) {
            $query->where("user_posts_id", ">", $greater_than);
        }

        return $query->orderBy("user_posts_id", "DESC")->limit($limit);
    }

    /**
     * 
     * @param type $query
     * @param type $totalFollowers
     * @param type $user_id
     */
    public static function getHomeSubQuery($query, $totalFollowers, $user_id) {
        if ($totalFollowers > 500) {
            $query->where(function ($query) use ($user_id) {
                $query->where("user_id", "=", $user_id)
                        ->orWhere(function ($query) use ($user_id) {
                            $query->whereHas("following", function ($sql) use ($user_id) {
                                $sql->where("follower_id", "=", $user_id);
                                $sql->where("status", "=", "A");
                            });
                        });
            });
        } else {
            $users_list = array_merge(Friend::getFollowingList($user_id), [$user_id]);
            $query->whereIn("user_id", $users_list);
        }
        return $query;
    }

    /**
     * 
     * @param type $user_id
     * @param type $post_types
     * @return type
     */
    private static function getHomeQuery($user_id, $post_types = []) {
        $query = PostBox::distinct("user_posts_id")->select("post_type_id", "user_posts_id", "archive")
                ->with(PostRelationHelper::postCommonRelations());
        $query->with(PostRelationHelper::postBoxCommonRelations($user_id));
        return $query;
    }

    /**
     * Get search post detail attributes
     * @param type $post_id
     * @return type
     */
    public static function getSearchPostAttributes($post_id) {
        $result = PostBox::distinct("user_posts_id")->select("post_type_id", "user_posts_id")
                ->where('user_posts_id', '=', $post_id)
                ->whereHas('post')
                ->with(['post' => function ($sql) {
                        $columns = ["id", \DB::raw("id as postable_id,postable_type")];
                        $sql->select($columns);
                        $sql->with(["postable"]);
                    }])
                ->first();
        return !empty($result) ? $result->post->toArray() : [];
    }

    /**
     * 
     * @param type $inputs
     *      only post_id is being used from inputs
     * @param type $user_id
     * @return type
     */
    public static function getNewPostDetail($inputs = [], $user_id) {
        $query = PostBox::distinct("user_posts_id")->select("post_type_id", "user_posts_id", "archive")
                ->where(function ($sql) use ($inputs) {
                    $sql->where('user_posts_id', '=', $inputs['post_id']);
                })
                ->with(PostRelationHelper::newPostCommonRelations($user_id, true));
        $query->with(PostRelationHelper::postBoxCommonRelations($user_id, true));
        $post = $query->first();
        if (!empty($post)) {
            $post_fill = [];

            $medias = PostMedia::getPostMedia([$post["user_posts_id"]]);

            list($reactions, $candidates) = Like::getPostDistinctReactions([$post["user_posts_id"]]);

            //Setting is liked
            $is_liked = Like::getPostIsLikedReactions($user_id, [$post->post->id]);
            if (!empty($is_liked[$user_id][$post->post->id])) {
                $post_fill["postLikesByUser"] = (object) $is_liked[$user_id][$post->post->id];
            }

            $candidates[$post->post["user_id"]] = $post->post["user_id"];
    
            $candidate_ids = array_values($candidates);

            $friends = Friend::getListFromCacheAndDB($user_id, $candidate_ids);


            $users = User::getCachedUsers($candidate_ids);

            $post_owner = $users[$post->post["user_id"]];
            if ($post->post->user_id !== $user_id) {
                $post_owner["is_followed"] = $friends[$post->post->user_id];
            }

            $transformed_reactions = self::transformReactions($reactions, $users, $friends);

            $media = collect(json_decode(json_encode($medias[$post["user_posts_id"]])));

            $fill = ["postMedia" => $media, "user" => $post_owner, "likes" => $transformed_reactions];
         
            /**
             * Setting bookmark
            */
            if ($post->post->user_id != $user_id) {
                $bookMarks = BookMark::getBookMarkedStatuses($user_id, "P", [$post->post->id]);

                if (isset($bookMarks[$post->post->id]) && $bookMarks[$post->post->id]["status"] == "A") {
                    $post_fill["isBookmarked"] = $bookMarks[$post->post->id]["status"];
                }
            }

            $post->forceFill($post_fill);

            if (!empty($post->post->location_id) && $post->post->location_id > 0) {

                $location = Location::getPostPlaceFromCached($post->post->location_id);
                $attached_place = collect([(object) $location[$post->post->location_id]]);
                $fill["place"] = $attached_place->first();
            }
            
            if ($post->post_type_id == 7) {
                $fill['postable'] = self::getCachedPostable($post->post);
            }
            $post->post->forceFill($fill);
          
            return $post;
        } else {
            return [];
        }
    }

    private static function transformReactions($group_of_reactions, $users, $friends) {
        $transformed = [];

        foreach ($group_of_reactions as $post_id => $reactions) {
            foreach ($reactions as $reaction) {
                $user = $users[$reaction["liked_by"]];
                $user["is_followed"] = $friends[$user["id"]] == 'A' || $friends[$user["id"]] == 'P' ? $friends[$user["id"]] : "";
                $transformed[$post_id][] = ["id" => $reaction['id'], "reaction_id" => $reaction["reaction_id"], "user" => $user];
            }
        }

        return $transformed;
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    private static function getCachedPostable($post) {
        $source_key = "{$post->source_type}-{$post->source_id}@{$post->item_type_number}";
        if ($post->source_type == "google") {
            $locations = Location::getLocationsForCached([$source_key]);
            return $locations[$source_key];
        } else {
            $attrs = PostSearchAttribute::getAttrsForCached([$source_key]);
            return $attrs[$source_key];
        }
    }

}
