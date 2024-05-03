<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

/**
 * Description of PostRelationHelper
 *
 * @author ali
 */
class PostRelationHelper {
    //put your code here

    /**
     * 
     * @param type $user_id
     * @param type $is_full
     * @return type
     */
    public static function getRelations($user_id, $is_full) {
        $query1 = ['postMedia' => function ($query) use ($user_id) {
                $query->select('file', 'bucket', 'file_base_name', 'file_height', 'file_type', 'file_type_number', "bg_color", 'file_width', 'medium_file_height', 'medium_file_width', 'user_post_id', "collage_file_width", "collage_file_height");
            }, 'postable'];

        if (!$is_full) {
            return $query1;
        } else {
            // moved to PostBoxes model
            $query2 = ['user' => function ($query) {
                    $query->select('id', "uid", 'username', 'picture', 'bucket', 'is_live');
                }];
            // moved to UserPost model
            $query3 = ['place' => function ($query) {
                    $query->select('id', 'latitude', 'longitude', 'location_name', 'fs_location_id', 'address');
                }];
            // moved to UserPost model
            $query4 = ['postBoxesPivot' => function ($query) {
                    $query->select('box_id', 'name', 'user_posts_boxes.archive', 'box.status');
                }];
            // move to post boxes due to morphic relationships
            $query6 = ['postLikesByUser' => function ($query) use ($user_id) {
                    $query->select('id', 'liked_to');
                    $query->where("liked_by", "=", $user_id);
                }];
            // move to post boxes due to morphic relationships
            $query7 = ['postTotalLikes', 'postTotalComments', 'postTotalTags'];

            return array_merge($query1, $query2, $query3, $query4, $query6, $query7);
        }
    }

    /**
     * Get post box / post common relations
     * @return type
     */
    public static function postBoxCommonRelations($user_id, $attachLikeComment = false) {
        $lastCommentLike = [];
        $blockIds = \App\Models\BlockedUser::cachedUserBlockList($user_id);
        $simpleRelations = ['postTotalLikes', 'postTotalComments', 'postTotalTags' => function ($sql) use ($blockIds) {
                if ($blockIds) {
                    $sql->whereNotIn('user_id', $blockIds);
                }
            }];
        $likeByUser = ['postLikesByUser' => function ($sql) use ($user_id) {
                $sql->select('id', 'liked_to', 'reaction_id');
                $sql->where("liked_by", "=", $user_id);
            }];
        if ($attachLikeComment) {
            $lastCommentLike = ['comments' => function ($sql) {
                    $sql->select(["id", "user_id", "user_post_id", "comment", "created_at"]);
                    $sql->with("user");
                },
//                'likes' => function ($sql) use ($user_id) {
//                    $sql->select(["id", "liked_by", "liked_to", "created_at", "reaction_id"]);
//                    $sql->with(["user" => function ($sql) use ($user_id) {
//                            $sql->select(array('id', 'uid', 'username', 'full_name', 'picture', 'bucket', 'is_verified'));
//                            $sql->with(["isFollowed" => function ($sql) use ($user_id) {
//                                    $sql->where('follower_id', '=', $user_id);
//                                    $sql->whereIn('status', ['A', 'P']);
//                                }]);
//                        }]);
//                },
//                'isBookmarked' => function ($sql) use ($user_id) {
//                    $sql->where('user_id', "=", $user_id);
//                    $sql->select(array("id", "relation_id"));
//                }
            ];
        }
        return array_merge($simpleRelations, $lastCommentLike);
    }

    /**
     * Get user post common relations
     * @return type
     */
    public static function postCommonRelations($is_detail = false) {
        $relations = ["post" => function ($sql) use ($is_detail) {
                $columns = ["id", "text_content", "created_at",
                    "local_db_path", "user_id", "post_type_id", "web_url",
                    "client_ip_address", "client_ip_latitude", "client_ip_longitude",
                    "location_id", "short_code", \DB::raw("id as postable_id,postable_type")];
                $sql->select($columns);
                $sql->with(['user' => function ($sql) {
                        $sql->select('id', "uid", 'username', 'picture', 'bucket', 'is_live');
                    }, 'postable' => function ($sql) use ($is_detail) {
                        if ($is_detail && in_array(config('general.post_type_index.search'), ["postable.post_type_id"])) {
                            $sql->with(["postable"]);
                        }
                    }]);
                $sql->with(["postBoxesPivot" => function ($sql) {
                        $sql->select('box_id', 'name', 'user_posts_boxes.archive', 'box.status');
                    }]);
                $sql->with("place");
            }];
        return $relations;
    }

    public static function newPostCommonRelations($user_id = 0, $is_detail = false) {
        $relations = ["post" => function ($sql) use ($is_detail, $user_id) {
                $columns = ["id", "text_content", "created_at",
                    "local_db_path", "user_id", "post_type_id", "web_url", "archive",
                    "client_ip_address", "client_ip_latitude", "client_ip_longitude",
                    "location_id", "short_code",
                    "source_id", "source_type", "item_type_number", "api_attribute_id"];
                // \DB::raw("id as postable_id,postable_type"),
                $sql->select($columns);
                $sql->with(
                        [
//                            'user' => function ($sql) use ($user_id) {
//                                $sql->select('id', "uid", "full_name", 'username', 'picture', 'bucket', 'is_live', 'is_verified');
//                                if ($user_id > 0) {
////                            $sql->with(["isFollowed" => function ($sql) use ($user_id) {
////                                    $sql->where('follower_id', '=', $user_id);
////                                    $sql->whereIn('status', ['A', 'P']);
////                                }]);
//                                }
//                            },
//                    'postable' => function ($sql) use ($is_detail) {
//                        if ($is_detail && in_array(config('general.post_type_index.search'), ["postable.post_type_id"])) {
//                            $sql->with(["postable"]);
//                        }
//                    }
                ]);
                $sql->with(["postBoxesPivot" => function ($sql) {
                        $sql->select('box_id', 'name', 'user_posts_boxes.archive', 'box.status');
//                        $sql->with(["boxLastPost.postMedia"]);
                    }]);
//                $sql->with("place");
            }];
        return $relations;
    }

}
