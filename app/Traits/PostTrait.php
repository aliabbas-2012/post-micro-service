<?php

namespace App\Traits;

use App\Models\BlockedUser;
use App\Models\Friend;
use GuzzleHttp\Client;

/**
 * Description of CommonTrait
 *
 * @author rizwan
 */
trait PostTrait {

    public function getRelations($user_id, $likes_and_comments = false) {
        $query3 = ['postMedia' => function ($query) {
                $query->select('file', 'bucket', 'file_base_name', 'file_height', 'file_width', 'file_type', 'file_type_number', "is_primary", 'medium_file_height', 'medium_file_width', 'collage_file_height', 'collage_file_width', 'thumb_file_height', 'thumb_file_width', 'home_file_height', 'home_file_width', 'user_post_id', "bucket");
            }];
        $query4 = ['postBoxesPivot' => function ($query) {
                $query->select('box_id', 'name', 'user_posts_boxes.archive', 'box.status');
            }];
        $query6 = ['postLikesByUser' => function ($query) use ($user_id) {
                $query->select('id', 'liked_to');
                $query->where("liked_by", "=", $user_id);
            }];
        $query7 = ['postTotalTags', 'postTotalLikes', 'postTotalComments'];
        if ($likes_and_comments) {
            $otherRelations = $this->getLikeAndCommentRelations($user_id);
            $query8 = $otherRelations[0];
            $query9 = $otherRelations[1];
            return array_merge($query3, $query4, $query8, $query9, $query7);
        } else {
            return array_merge($query3, $query4, $query6, $query7);
        }
    }

    public function getLikeAndCommentRelations($user_id) {
        $query8 = ['postLikes' => function ($query) use ($user_id) {
                $this->extendPostLikes($query, $user_id);
            }];
        $query9 = ['postComments' => function ($query) use ($user_id) {
                $this->extendPostComments($query, $user_id);
            }];
        return [$query8, $query9];
    }

    public function extendPostLikes($query, $user_id) {
        $query->select('id', 'liked_to', 'liked_by');

        $query->with(['user' => function ($query) use ($user_id) {
                $this->extendPostLikeUser($query, $user_id);
            }]);

        $query->limit(40);
        return $query;
    }

    public function extendPostLikeUser($query, $user_id) {
        $query->select('id', 'uid', 'username', 'full_name', 'is_live', 'picture', 'bucket');
        $query->with(['isFriend' => function ($query) use ($user_id) {
                $query->where('follower_id', '=', $user_id);
                $query->where(function ($query) {
                    $query->where('status', '=', 'A')->orWhere('status', '=', 'P');
                });
            }]);
        return $query;
    }

    public function extendPostComments($query, $user_id) {
        $query->select('id', 'comment', 'user_post_id', 'user_id', 'created_at');
        $query->where("archive", "=", false);
        $query->with(['user' => function ($query) use ($user_id) {
                $query->select('id', 'uid', 'username', 'full_name', 'is_live', 'picture', 'bucket');
            }]);
        $query->with(['commentMention' => function ($query) use ($user_id) {
                $this->extendPostCommentMentionUser($query, $user_id);
            }]);
        return $query->limit(40);
    }

    public function extendPostCommentMentionUser($query, $user_id) {
        $query->select('id', 'post_comments_id', 'user_posts_id', 'user_id');
        $query->with(['user' => function ($query) use ($user_id) {
                $query->select('id', 'uid', 'username', 'full_name', 'is_live', 'picture', 'bucket');
            }]);
        return $query;
    }

    /**
     * check post viewed privacy allowed or not
     * @param type $post
     * @param type $login_user
     * @return boolean
     */
    public function validatePostViewPrivacy($post, $login_user) {
        $response = false;
        if ($login_user->id == $post->user_id) {
            $response = true;
        } else {


            $postUser = empty($post->user) ? \App\Models\User::where('id', '=', $post->user_id)->first()->toArray() : $post->user;

            if (empty($postUser) || $postUser["archive"]) {
                return false;
            }
            $response = $this->secondStepToValidateViewPrivacy($login_user, $postUser, $post);
        }
        return $response;
    }

    public function secondStepToValidateViewPrivacy($login_user, $postUser, $post) {
        $response = false;
        if (!BlockedUser::checkbBlockUser($login_user->id, $postUser["id"])) {


            $is_following = false;
            if (isset($postUser["is_followed"])) {
                $is_following = $postUser["is_followed"] == "A";
            } else {
                $is_following = Friend::checkUserFollowingFollower($login_user->id, $postUser->id, false);
            }

            //if person is private and you are not following
            if (!$postUser["is_live"] && !$is_following) {
                return false;
            }

            $response = $this->checkBoxPrivacy($post, $postUser, $is_following);
        }
        return $response;
    }

    /**
     * Check box privacy
     * @param type $post
     * @param type $postUser
     * @param type $is_following
     * @return type
     */
    public function checkBoxPrivacy($post, $postUser, $is_following = true) {
        $response = false;
        $boxes = !empty($post->postBoxesPivot) ? $post->postBoxesPivot : $post->postBoxesPivot();

        if (!$boxes->isEmpty()) {
            $response = $this->boxPrivacyValidation($boxes, $is_following, $postUser);
        }
        return $response;
    }

    /**
     * archive Post
     * @param type $post
     * @param type $token
     */
    private function removeEsPost($post_id, $token) {
        try {
            $url = config("general.upload_post_micro_service") . "archiveESPost";
            $newClient = new Client();
            $request = $newClient->delete($url, [
                'headers' => ['token' => $token, "auth_key" => config("general.auth_key"), 'user-agent' => config("general.internal_service_id")],
                "query" => [
                    "post_id" => $post_id
                ]
            ]);

            return $request->getStatusCode() == 200 ? true : false;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Validate user post privacy
     * @param type $post
     * @param type $user
     * @return type
     */
    public function isAllowedPostView($post, $user) {
        $allowed = false;
        if ($post->post->user_id == $user->id) {
            $allowed = true;
        } else {
            $allowed = $this->validateUserAllowedPrivacy($user, $post->post->user, $post);
        }
        return $allowed;
    }

    /**
     * Validate user and box privacy
     * @param type $login_user
     * @param type $postUser
     * @param type $post
     * @return boolean
     */
    public function validateUserAllowedPrivacy($login_user, $postUser, $post) {
        $response = false;
        if (!BlockedUser::checkbBlockUser($login_user->id, $postUser->id)) {
            $is_following = Friend::checkUserFollowingFollower($login_user->id, $postUser->id, false);
            //if person is private and you are not following
            if ($postUser->is_private && !$is_following) {
                return false;
            }
            $response = $this->boxPrivacyValidation($post->post->postBoxesPivot, $is_following, $postUser);
        }
        return $response;
    }

    /*
     * update Short Code Post
     * @param type $post
     * @param type $token
     */

    private function updateShortCode($post, $token) {
        if ($post['post_type_id'] == config("general.post_type_index.product") && empty($post["short_code"])) {
            try {
                $url = config("general.upload_post_micro_service") . "updateShortCode";
                $newClient = new Client();
                $request = $newClient->put($url, [
                    'headers' => ['token' => $token, "auth_key" => config("general.auth_key"), 'user-agent' => config("general.internal_service_id")],
                    "query" => [
                        "post_id" => $post["id"]
                    ]
                ]);

                return $request->getStatusCode() == 200 ? true : false;
            } catch (\Exception $ex) {
                return false;
            }
        }
    }

}
