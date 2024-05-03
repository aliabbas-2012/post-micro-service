<?php

namespace App\Transformers;

/**
 * Post Transformer
 * @author ali
 */
class GenericPostTransformer extends BaseTransformer {

    use \App\Traits\ApiPostTransformerTrait;

    /**
     * It will only contains post and its first media URL
     * @param type $arr
     * @return type
     */
    public function transform($arr) {
        $post = $arr["p"];
        $user = $arr["u"];
        $ip = $arr["ip"];
        $inputs = $arr["inputs"];
        return $this->preparePostResponseWithRelations($post, $user, $ip, $inputs);
    }

    /**
     * 
     * @param type $post
     * @param type $user
     * @param type $ip
     * @return type
     */
    public function preparePostResponseWithRelations($post, $user, $ip, $inputs = []) {

        $data = $this->preparePostResponse($post, $user, $ip, $inputs);
//        if (in_array($post->post->post_type_id, [1, 2, 5])) {
//            $data["media_post"] = $this->prepareMediaPostAttributes($post);
//            $data["media_post"]["media"] = $this->prepareMedia($post->post->postable);
//        }
//        $data["boxes"] = $this->prepareBoxes($post->post);
//        $data['comments'] = $this->prepareComment($post);
//        $data['likes'] = $this->prepareLikes($post, $user);

        return $data;
    }

    public function prepareComment($post) {
        $response = [];
        if (!empty($post->comments)) {
            foreach ($post->comments as $comment) {
                $response[] = $this->prepareCommentObject($comment);
            }
        }
        return $response;
    }

    /**
     * Prepare Load post comments
     * @param type $comments
     * @return type
     */
    public function prepareLoadComments($comments) {
        $response = [];
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $response[] = $this->prepareCommentObject($comment);
            }
        }
        return $response;
    }

    public function prepareCommentObject($comment) {
        return [
            'id' => (int) $comment->id,
            'comment' => (string) $comment->comment,
            'created_at' => $this->parseCarbonFormat($comment->created_at),
            'user' => $this->prepareUser($comment)
        ];
    }

    public function prepareLikes($post, $user) {
//        $priority_user = ($user->id == $post->post->user_id) ? $user->id : 0;
        $likes = \App\Models\Like::getPostDistinctReactions([$post->user_posts_id]);
        $response = [];
        if (!empty($post->likes)) {
            foreach ($post->likes as $value) {
                $like = [];
                $like = [
                    "id" => $value->id,
                    "reaction_id" => (int) $value->reaction_id,
                    "created_at" => $this->parseCarbonFormat($value->created_at),
                    "user" => $this->prepareUser($value)
                ];
                $like['user']['follow_status'] = !empty($value->user->is_followed) ? $value->user->is_followed->status : "";
                $response[] = $like;
            }
        }
        return $response;
    }

    /**
     * 
     * @param type $post
     * @param type $user
     * @param type $ip
     * @return type
     */
    public function preparePostResponse($post, $user, $ip, $inputs = []) {
        $env = env("APP_ENV") == 'production' ? 'p' : 's';
        $data = [
            "id" => $post->post->id,
            "local_db_path" => !empty($post->post->local_db_path) ? $post->post->local_db_path : "",
            "content" => !empty($post->post->text_content) ? trim($post->post->text_content) : "",
            "is_liked" => !empty($post->postLikesByUser) ? true : false,
            "reaction_id" => !empty($post->postLikesByUser) ? (int) $post->postLikesByUser->reaction_id : 0,
            "like_count" => !empty($post->postTotalLikes) ? $post->postTotalLikes->total_likes : 0,
            "comment_count" => !empty($post->postTotalComments) ? $post->postTotalComments->total_comments : 0,
            "tag_count" => !empty($post->postTotalTags) ? $post->postTotalTags->total_tags : 0,
            "created_at" => $post->post->created_at_app,
            "short_url" => $this->prepareShortUrl($post),
            "short_code" => !empty($post->post->short_code) ? $post->post->short_code : "", // Temporarily added until removed from front-end code
            "is_bookmarked" => !empty($post->isBookmarked) ? true : false,
            "post_type" => (int) $post->post->post_type_id,
            "type" => (int) $post->post->post_type_id, // Only user for is_fayved checking
            "entity_type" => "P",
            "categories" => [],
            "view_count" => 0,
            "user" => $this->prepareUser($post->post)
        ];
        return $this->postApiAttributes($post, $data, $user, $ip, $inputs);
    }

    /**
     * Prepare post search attributes array
     * @param type $post
     * @param type $data
     * @param type $user
     * @param type $ip
     * @return type
     */
    public function postApiAttributes($post, $data, $user, $ip, $inputs = []) {

        if ($post->post->post_type_id == config("general.post_type_index.search")) {
            $fayvs = !empty($post->post->postable->postable) ? $post->post->postable->postable->fayvs : 0;
            $caption = $post->post->postable->source_type == "google" ? $post->post->postable->postable->location_type : "";

            $thumb_info = $this->getApiPostThumbInfo($post->post->postable->source_type, $post->post);

            $data["api_post"] = [
                "is_available" => true,
                "title" => (string) $post->post->postable->title,
                "thumb" => $thumb_info['thumb'],
                "bg_color" => $thumb_info['bg_color'],
                "is_blur" => !$thumb_info['thumb_status'],
                "item_type" => trans("messages.itemType" . ucfirst(strtolower($post->post->postable->item_type))),
                "item_type_number" => (int) $post->post->postable->item_type_number,
                "source_type" => $post->post->postable->source_type,
                "source_id" => $post->post->postable->source_id,
                "item_icon" => $this->prepareItemIconUrl($post->post->postable->source_type, $caption, config("general.item_type_by_number.{$post->post->postable->item_type_number}")),
                "source_icon" => config('g_map.source_icon_base_url') . $post->post->postable->source_type . ".png",
                "is_fayved" => false,
                "fayvs" => (int) $fayvs,
                "source_url" => $this->prepareSourceUrl($post->post->postable, $user),
//                'original_source_link' => !empty($post->post->postable->source_link) ? $post->post->postable->source_link : "",
                "post_description" => ($post->post->source_type != "web" && !empty($post->post->postable->postable->description)) ? strip_tags($post->post->postable->postable->description) : (($post->post->source_type == "web" && !empty($post->post->postable->bg_image)) ? strip_tags($post->post->postable->bg_image) : ""),
            ];
            /*
              if ($key = $this->isUpdateIconRequired($post->post->postable->item_type_number, $inputs)) {
              $data["api_post"]['item_icon'] = config("general.item_type_icons.game");
              }
             * 
             */
            // will call node js for post attributes
            $data = $this->prepareApiPostSubModule($data, $post, $ip);
        }
        return $data;
    }

    private function getApiPostThumbInfo($source_type = "imdb", $post = null) {
        $thumb = [];
        $attr = $post->postable->postable->toArray();
        $thumb_status = isset($attr["thumb_status"]) && $attr["thumb_status"] ? true : false;
        if ($source_type == "google" && !$thumb_status) {
            $thumb = [
                "thumb_status" => false,
                "thumb" => $this->prepareMapScreenPath($attr),
                "bg_color" => config('g_map.map_default_bg_color'),
            ];
        } else {
            $source_id = ($source_type == "google") ? $attr["fs_location_id"] : $attr["source_id"];
            $thumbnail = !empty($attr["thumbnail"]) ? $attr["thumbnail"] : $this->getPreviewCdn($source_id, $source_type, $attr["item_type_number"]);
            //sending default bg color
            $thumb = [
                "thumb_status" => boolval($attr["thumb_status"]),
                "thumb" => "{$thumbnail}",
                "bg_color" => "#" . str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT),
            ];
            if (isset($attr["bg_color"]) && !empty($attr["bg_color"])) {
                $thumb["bg_color"] = "{$attr["bg_color"]}";
            } else if (!empty($post->postable->postMedia) && !empty($post->postable->postMedia[0])) {
                $thumb["bg_color"] = "{$post->postable->postMedia[0]->bg_color}";
            }
        }
        return $thumb;
    }

    public function prepareMediaPostAttributes($post) {
        $response = [];
        $response["web_url"] = !empty($post->post->web_url) ? $post->post->web_url : "";
        $response['tag_count'] = !empty($post->postTotalTags) ? (int) $post->postTotalTags->total_tags : 0;
        if (!empty($post->post->place)) {
            $response['place'] = $this->prepareMediaPostPlace($post->post->place, false);
        }
        return $response;
    }

    /**
     * Prepare media post attached location response
     * @param type $place
     * @return type
     */
    public function prepareMediaPostPlace($place, $isLocationPost = false) {

        $location = [];
        $location["id"] = $place->fs_location_id;
        $location["name"] = $place->location_name;
        $location["types"] = $this->processTags($place->tags);
        $location["lat"] = !empty($place->latitude) ? (string) $place->latitude : "";
        $location["lon"] = !empty($place->longitude) ? (string) $place->longitude : "";
        $location["category"] = ""; // Currently ML Team working on it
        if ($isLocationPost) {
            $location["map_screen"] = $this->prepareMapScreenPath($place->toArray());
        }
        return $location;
    }

    private function processTags($tags = "") {
        $response = "";
        if (!empty($tags)) {
            $tags = array_filter(explode(",", str_replace("_", " ", $tags)));
            $tags = array_map("ucwords", array_map('strtolower', $tags));
            $response = implode(",", $tags);
        }
        return $response;
    }

    /**
     * 
     * @param array $data
     * @param type $post
     * @param type $ip
     * @return type
     */
    private function prepareApiPostSubModule($data, $post, $ip) {
        if ($post->post->postable->postable->item_type_number != 5 && !$post->post->postable->postable->is_processed) {
            $client = new \App\Helpers\GuzzleHelper();
            $item_type_number = $post->post->postable->postable->item_type_number;
            $post_id = $post->post->id;
            $preview = $client->getApiPostRelatedData($post_id, $post->post->postable->source_type, $post->post->postable->source_id, $item_type_number, $ip);
            $data["api_post"]["is_available"] = ($post->post->postable->source_type != "web" && empty($preview)) ? false : true;
            $data["api_post"] += $this->prepareAPIResponse($post->post->postable->postable, $preview);
        } else {
            $data["api_post"]["is_available"] = boolval($post->post->postable->postable->is_available);
            $data["api_post"] += $this->prepareAPIResponse($post->post->postable->postable, null);
        }


        return $data;
    }

    /**
     * It will create a dynamic source url which will be navigated from our own analytics server 
     * and track the record
     * @param type $post
     * @param type $user
     * @return type
     */
    private function prepareSourceUrl($post, $user) {

        if ($analytic_server = config("general.analytics_server")) {
            $source_id = $post->source_id;
            $item_type_number = $post->item_type_number;
            $uid = base64_encode($user->uid);
            return $analytic_server . "?source_type={$post->source_type}&source_id={$source_id}&item_type_number={$item_type_number}&i={$uid}";
        } else if (!empty($post->source_link)) {
            return $post->source_link;
        }
        return "";
    }

    public function prepareMedia($post) {
        $medias = [];
        foreach ($post->postMedia as $post_media) {
            array_push($medias, $this->extractWidthHeightImages($post_media));
        }
        return $medias;
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    public function preparePlace($post, $requireFullInfo = false) {
        try {
            $place = [
                "latitude" => $post->place->latitude,
                "longitude" => $post->place->longitude,
                "location_name" => $post->place->location_name,
                "fs_location_id" => $post->place->fs_location_id,
            ];
            if ($requireFullInfo) {
                $place['location_type'] = !empty($post->place->location_type) ? $post->place->location_type : "";
                $place['location_address'] = !empty($post->place->address) ? $post->place->address : "";
                $place['bg_image'] = !empty($post->place->bg_image) ? $post->place->bg_image : "";
                $place['thumbnail'] = !empty($post->place->thumbnail) ? $post->place->thumbnail : "";
                $place['rating'] = (string) $post->place->rating;
                $place['map_placeholder'] = !empty($post->place->map_screen) ? $post->place->map_screen : "";
            }
            return $place;
        } catch (\Exception $ex) {
            $exception['messsage'] = $ex->getMessage();
            $exception['file'] = $ex->getFile();
            $exception['line'] = $ex->getLine();
            $exception['transofmrer'] = "Post Transofmer";
            $exception['data'] = $post->toArray();
            \Log::info($exception);
            return (new \stdClass());
        }
    }

    /**
     * @param type $post
     */
    public function prepareUser($post) {
        return [
            "id" => $post->user->uid,
            "username" => $post->user->username,
            "full_name" => $post->user->full_name,
            "picture" => $this->getUserPictureThumb($post->user),
            "is_private" => boolval($post->user->is_private),
            "is_verified" => boolval($post->user->is_verified),
            "follow_status" => isset($post->user->isFollowed) && !empty($post->user->isFollowed) ? $post->user->isFollowed->status : "",
        ];
    }

    /**
     * 
     * @param type $user
     * @return type
     */
    public function getUserPictureThumb($user) {

        if (!filter_var($user->picture, FILTER_VALIDATE_URL) === false) {
            return $user->picture;
        } else {
            return $user->thumb;
        }
    }

    /**
     * Prepare post boxes response array
     * @param type $post
     * @return array
     */
    public function prepareBoxes($post) {
        $boxes = $medias = [];
        foreach ($post->postBoxesPivot as $box) {
            $medias = [];
            $medias[0]['thumb'] = !empty($box->boxLastPost->postMedia) ? $box->boxLastPost->postMedia->thumb : "";
            $box_arr = ["id" => $box->box_id, "name" => $box->name, "status" => $box->status, 'media' => $medias];
            array_push($boxes, $box_arr);
        }
        return $boxes;
    }

    public function extractWidthHeightImages($post_media) {
        $media = array();
        //For older API
        $media['media_type'] = (int) $post_media->file_type_number;
        //For New API
        $media['type'] = (int) $post_media->file_type_number;
        switch ($post_media->file_type_number) {
            case 1:
                $media['actual'] = $post_media->original;
                $media['thumbnail'] = $post_media->home;
                break;
            case 2:
                $media['thumbnail'] = $post_media->home;
                $media['actual'] = $post_media->source;
                break;
            default:
                "";
        }
        $media['width'] = !empty($post_media->medium_file_width) ? (int) $post_media->medium_file_width : (int) $post_media->file_width;
        $media['height'] = !empty($post_media->medium_file_height) ? (int) $post_media->medium_file_height : (int) $post_media->file_height;
        $media['bg_color'] = (string) $post_media->bg_color;
        return $media;
    }

    public function prepareSingleMedia($post_media, $post_type = 0) {
        $media = array();
        $media['thumbnail'] = ($post_media->file_type_number == 1) ? $post_media->medium : $post_media->thumb;
        $media['bg_color'] = ($post_media->bg_color) ? $post_media->bg_color : config('general.default_bg_color');
        $media['media_type'] = !empty($post_media->file_type_number) ? $post_media->file_type_number : "";
        $media['post_type'] = ($post_type > 0) ? $post_type : "";
        $media['width'] = !empty($post_media->medium_file_width) ? (int) $post_media->medium_file_width : (int) $post_media->file_width;
        $media['height'] = !empty($post_media->medium_file_height) ? (int) $post_media->medium_file_height : (int) $post_media->file_height;
        return $media;
    }

    public function prepareSingleBoxMedia($post_media, $post_type = 0) {
        $media = array();
        switch ($post_media->file_type_number) {
            case 1:
                $media['thumbnail'] = $this->getPostCdnUrl($post_media->bucket) . config("image.post_images_medium") . $post_media->file;
                break;
            case 2:
                $media['thumbnail'] = $this->getPostCdnUrl($post_media->bucket) . config("image.post_videos_medium") . $post_media->file_base_name . ".jpg";
                break;
            default:
                "";
        }
        $media["bg_color"] = !empty($post_media->bg_color) ? $post_media->bg_color : config('general.default_bg_color');
        $media["media_type"] = $post_media->file_type_number;
        $media['post_type'] = ($post_type > 0) ? $post_type : "";
        if ($dimensions = $this->getDimensions($post_media, $media['thumbnail'])) {
            $media['width'] = $dimensions["width"];
            $media['height'] = $dimensions["height"];
        } else {
            $media['width'] = !empty($post_media->medium_file_width) ? (int) $post_media->medium_file_width : (int) $post_media->file_width;
            $media['height'] = !empty($post_media->medium_file_height) ? (int) $post_media->medium_file_height : (int) $post_media->file_height;
        }


        return $media;
    }

    /**
     * NOT IN USE
     * prepare box detail response
     * @param type $post
     * @return type
     */
    public function prepareBoxPostsResonse($post) {

        $data = [
            "id" => $post->id,
            "post_type" => $post->post_type_id,
            "media" => $this->prepareMedia($post),
            "created_at" => $post->created_at_app,
            "user" => $this->prepareUser($post)
        ];
        return $data;
    }

    private function getDimensions($post_media, $url) {
        $media = [];
        $media['width'] = !empty($post_media->medium_file_width) ? (int) $post_media->medium_file_width : (int) $post_media->file_width;
        $media['height'] = !empty($post_media->medium_file_height) ? (int) $post_media->medium_file_height : (int) $post_media->file_height;
        if (empty(array_filter($media))) {
            list($width, $height) = getimagesize($url);
            $media['width'] = $width;
            $media['height'] = $height;
        }
        return $media;
    }

    private function prepareShortUrl($post) {
        $short_url = "";
        if (config("general.base_app_version") >= 4.4) {
            if ($post->post->post_type_id == config("general.post_type_index.search")) {
                $source = "{$post->post->postable->source_type}-{$post->post->postable->source_id}@{$post->post->postable->item_type_number}";
                $short_url = $this->getShortUrl(['id' => $post->post->id, 'source_key' => $source], strtolower($post->post->postable->item_type), "P");
            } else {
                $short_url = $this->getShortUrl(['id' => $post->post->id, 'source_key' => $post->post->id], "media", "P");
            }
        } else {
            $env = env("APP_ENV") == 'production' ? 'p' : 's';
            $short_url = !empty($post->post->short_code) ? config("g_map.fayvo_share_base_url") . 't=pt&fsq=' . $post->post->short_code . '&en=' . $env : "";
        }
        return $short_url;
    }

    /**
     * NOT USED
     * Is updated item icon required
     * @param type $item_type_number
     * @param type $inputs
     * @return boolean|string
     */
    private function isUpdateIconRequired($item_type_number = 0, $inputs = []) {
        $isOldIcon = !isset($inputs['icon_update']) ? true : false;
        if ($isOldIcon && config("general.post_item_types.game") == $item_type_number) {
            return 'game_old';
        }
        return false;
    }

}
