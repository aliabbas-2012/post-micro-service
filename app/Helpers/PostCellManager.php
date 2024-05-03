<?php

namespace App\Helpers;

use App\Models\PostBox;
use App\Models\Box;
use App\Models\PostTagUser;
use App\Models\PostMedia;
use App\Models\Like;
use App\Models\UserPost as Post;
use App\Models\User;
use App\Models\Friend;
use App\Models\PostComment as Comment;
use App\Models\Location;
use App\Models\BoxBookmark as BookMark;
use App\Helpers\EsQueries\IsFayvedBuild;
use App\Models\PostSearchAttribute;

/**
 * This class is responsible for managing post detail 
 * @author ali abbas
 */
class PostCellManager {

    public $visitor_id;
    public $post_ids;
    public $model;

    public function __construct($post_ids, $visitor_id) {
        $this->post_ids = $post_ids;
        $this->visitor_id = $visitor_id;
    }

    public function handle() {
        $post_ids = $this->post_ids;

        $query = PostBox::distinct("user_posts_id")->select("post_type_id", "user_posts_id", "archive", "box_id")
                ->where(function ($sql) use ($post_ids) {
            $sql->whereIn('user_posts_id', $post_ids);
        });

        if ($postBoxes = $query->get()) {


            $this->setPostInfo();

            $this->setMedia();
            $this->setPostBoxes($postBoxes);
            $this->setReaction();
            $this->setPlaces();
            $this->setApiInfo();
            $this->setBookMarks();

            $tag_user_ids = $this->setPostTags();
            $candidates_reactions = $this->getReactions();
            $this->setLikeCommentCounts();
            $candidates_comments = $this->getTopComments();
            $candidates_post_owners = array_column($this->model["posts"], "user_id");

            $merged_candidate_ids = array_merge($candidates_reactions, $tag_user_ids, $candidates_comments, $candidates_post_owners);

            $unique_candidate_ids = array_unique($merged_candidate_ids);

            $users = User::getCachedUsers($unique_candidate_ids);
            $friends = Friend::getListFromCacheAndDB($this->visitor_id, $unique_candidate_ids);
            $this->bannerAttached();
            foreach ($this->model["posts"] as $post_id => $post) {
                $post_owner = $users[$post["user_id"]];
                $post_owner["is_followed"] = $friends[$post_owner["id"]] == 'A' || $friends[$post_owner["id"]] == 'P' ? $friends[$post_owner["id"]] : "";
                $this->model["posts"][$post_id]["user"] = $post_owner;

                $this->model["posts"][$post_id]["reactions"] = $this->transformReactions($users, $friends, $post_id);
                $this->model["posts"][$post_id]["comments"] = $this->transformComments($users, $friends, $post_id);
            }
        }
    }

    /**
     * 
     * @return boolean
     */
    public function validatePrivacy($post_id) {
        if ($this->model["posts"][$post_id]["user_id"] == $this->visitor_id) {
            return true;
        } else if ($this->model["posts"][$post_id]['post_type_id'] == config("general.post_type_index.product")) {
            // eCommerce product or ads
            return true;
        } else {
            $block_ids = \App\Models\BlockedUser::cachedUserBlockList($this->visitor_id);
            if (!in_array($this->model["posts"][$post_id]["user_id"], $block_ids)) {
                return $this->validateBoxes($post_id);
            }
        }


        return false;
    }

    /**
     * 
     * @return type
     */
    private function validateBoxes($post_id) {

        $match = false;

        foreach ($this->model["posts"][$post_id]["boxes"] as $box) {
            if ($this->model["posts"][$post_id]["user"]["is_followed"] == "A" && ($box["status"] == "F" || $box["status"] == "A")) {
                $match = true;
                break;
            } else if ($this->model["posts"][$post_id]["user"]["is_live"] && $box["status"] == "A") {
                $match = true;
                break;
            }
        }
        return $match;
    }

    /**
     * 
     * @return type
     */
    public function getModel($post_id) {
        return $this->model["posts"][$post_id];
    }

    public function exists($post_id) {
        if (!empty($this->model["posts"][$post_id])) {
            return !boolval($this->model["posts"][$post_id]["archive"]);
        }
        return false;
    }

    /**
     * it will set all the  boxes
     * @param type $postBoxes
     */
    public function setPostBoxes($postBoxes) {
        $box_ids = array_column($postBoxes->toArray(), "box_id");
        $boxes = Box::getCachedBoxes($box_ids);
        foreach ($postBoxes as $postBox) {
            $this->model["posts"][$postBox->user_posts_id]["boxes"][] = $boxes[$postBox->box_id];
        }
    }

    /**
     * 
     * @return type
     */
    private function setPostInfo() {
        $columns = ["id", "text_content", "created_at",
            "local_db_path", "user_id", "post_type_id", "web_url", "archive",
            "client_ip_address", "client_ip_latitude", "client_ip_longitude",
            "location_id", "short_code", \DB::raw("post_type_id as post_type"),
            "source_id", "source_type", "item_type_number", "api_attribute_id",
            "title", "price", "price_unit", "category"];
        if ($posts = Post::select($columns)->where("id", $this->post_ids)->get()) {

            foreach ($posts as $post) {
                $this->model["posts"][$post->id] = $post->toArray();
            }
        }
    }

    /**
     * Set Post Media
     */
    public function setMedia() {
        $medias = PostMedia::getPostMedia($this->post_ids);

        foreach ($medias as $post_id => $media) {
            $this->model["posts"][$post_id]["media"] = $media;
        }
    }

    /**
     * 
     */
    public function setPostTags() {
        $blockIds = \App\Models\BlockedUser::cachedUserBlockList($this->visitor_id);
        list($postTags, $tag_user_ids) = PostTagUser::getPostTags($this->post_ids, $blockIds);

        foreach ($this->post_ids as $post_id) {

            $count = isset($postTags[$post_id]) ? count($postTags[$post_id]) : 0;
            $this->model["posts"][$post_id]["tag_count"] = $count;
        }

        return $tag_user_ids;
    }

    /**
     * Set Reaction of each post
     * @return type
     */
    private function setReaction() {

        $reactionByPosts = Like::getPostIsLikedReactions($this->visitor_id, $this->post_ids);
        foreach ($this->post_ids as $post_id) {
            $this->model["posts"][$post_id]["reaction"] = !empty($reactionByPosts[$this->visitor_id][$post_id]) ? $reactionByPosts[$this->visitor_id][$post_id] : false;
        }
    }

    /**
     * 
     */
    private function setPlaces() {
        $location_ids = array_column($this->model["posts"], "location_id");
        $locations = Location::getPostPlaceFromCached($location_ids);
        foreach ($this->model["posts"] as $post_id => $post) {
            $place = isset($locations[$post["location_id"]]) ? $locations[$post["location_id"]] : [];
            $this->model["posts"][$post_id]["place"] = $place;
        }
    }

    /**
     * Reactions
     */
    private function getReactions() {
        list($reactions, $candidate_ids) = Like::getPostDistinctReactions($this->post_ids, $this->visitor_id);
        foreach ($this->post_ids as $post_id) {
            $this->model["posts"][$post_id]["reactions"] = !empty($reactions[$post_id]) ? $reactions[$post_id] : [];
        }
        return $candidate_ids;
    }

    /**
     * 
     */
    private function setLikeCommentCounts() {
        $likes = Like::getLikeCount($this->post_ids);
        $comments = Comment::getCommentCount($this->post_ids);
        foreach ($this->post_ids as $post_id) {
            $this->model["posts"][$post_id]["like_count"] = isset($likes[$post_id]["total"]) ? $likes[$post_id]["total"] : 0;
            $this->model["posts"][$post_id]["comment_count"] = isset($comments[$post_id]["total"]) ? $comments[$post_id]["total"] : 0;
        }
    }

    /**
     * 
     * @return type
     */
    private function getTopComments() {
        list($topGroupComments, $candidate_ids) = Comment::getTopPostComments($this->visitor_id, $this->post_ids);
        foreach ($this->post_ids as $post_id) {
            $top_comments = isset($topGroupComments[$post_id]) ? $topGroupComments[$post_id] : [];
            $this->model["posts"][$post_id]["comments"] = $top_comments;
        }

        return $candidate_ids;
    }

    /**
     * 
     * @param type $users
     * @param type $friends
     * @param type $post_id
     */
    private function transformReactions($users, $friends, $post_id) {

        $transformed = [];
        if (!empty($this->model["posts"][$post_id]["reactions"])) {
            foreach ($this->model["posts"][$post_id]["reactions"] as $reaction) {
                $user_id = $reaction["liked_by"];
                $user = $users[$user_id];
                $user["is_followed"] = $friends[$user["id"]] == 'A' || $friends[$user["id"]] == 'P' ? $friends[$user["id"]] : "";
                $transformed[] = ["id" => $reaction['id'], "reaction_id" => $reaction["reaction_id"], "created_at" => $reaction["created_at"], "user" => $user];
            }
        }
        return $transformed;
    }

    /**
     * 
     * @param type $users
     * @param type $friends
     * @param type $post_id
     */
    private function transformComments($users, $friends, $post_id) {

        $transformed = [];
        if (!empty($this->model["posts"][$post_id]["comments"])) {
            foreach ($this->model["posts"][$post_id]["comments"] as $comment) {
                $user_id = $comment["user_id"];
                $user = $users[$user_id];
                $user["is_followed"] = $friends[$user["id"]] == 'A' || $friends[$user["id"]] == 'P' ? $friends[$user["id"]] : "";
                $transformed[] = ["id" => $comment['id'], "comment" => $comment["comment"], "created_at" => $comment["created_at"], "user" => $user];
            }
        }
        return $transformed;
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    private function setApiInfo() {
        $source_keys = ["google" => [], "api" => []];
        foreach ($this->model["posts"] as $post_id => $post) {
            if ($post["post_type_id"] == config("general.post_type_index.api")) {
                $source_key = "{$post["source_type"]}-{$post["source_id"]}@{$post["item_type_number"]}";
                $this->model["posts"][$post_id]["source_key"] = $source_key;

                if ($post["source_type"] === "google") {
                    $source_keys["google"][] = $source_key;
                } else {
                    $source_keys["api"][] = $source_key;
                }
            }
        }

        $app_attrs = [];
        if (!empty($source_keys["api"])) {
            $app_attrs += PostSearchAttribute::getAttrsForCached($source_keys["api"]);
        }
        if (!empty($source_keys["google"])) {
            $app_attrs += Location::getLocationsForCached($source_keys["google"]);
        }


        foreach ($this->model["posts"] as $post_id => $post) {
            if ($post["post_type_id"] == config("general.post_type_index.api") && isset($app_attrs[$post["source_key"]])) {
                $this->model["posts"][$post_id]["api_attributes"] = $app_attrs[$post["source_key"]];
            }
        }
        $this->setPostsIsFavyed();
    }

    /**
     * 
     * @return type
     */
    private function setPostsIsFavyed() {
        $fayved_builder = new IsFayvedBuild();
        $this->model["posts"] = $fayved_builder->processIsFayved($this->visitor_id, $this->model["posts"]);
    }

    private function setBookMarks() {
        $bookMarks = BookMark::getBookMarkedStatuses($this->visitor_id, "P", $this->post_ids);

        foreach ($this->model["posts"] as $post_id => $post) {
            $this->model["posts"][$post_id]["is_bookmarked"] = !empty($bookMarks[$post_id]) ? true : false;
        }
    }

    /**
     * check banner attached with post
     */
    private function bannerAttached() {
        $banners = \App\Models\Banners\Banner::getPostsBanners($this->post_ids);
        foreach ($this->model["posts"] as $post_id => $post) {
            $this->model["posts"][$post_id]["is_banner"] = !empty($banners[$post_id]) ? true : false;
        }
    }

}
