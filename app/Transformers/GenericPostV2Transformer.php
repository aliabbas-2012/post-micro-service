<?php

namespace App\Transformers;

use Carbon\Carbon;

/**
 * Post Transformer
 * @author ali
 */
class GenericPostV2Transformer extends BaseTransformer {

    use \App\Traits\ApiPostTransformerTrait;

    /**
     * It will only contains post and its first media URL
     * @param type $arr
     * @return type
     */
    public function transform($arr) {
        list($post, $user, $inputs) = $arr;
        $obj = $post;
        $resp = $this->preparePostResponseWithRelations($post, $user, $inputs);
        return $resp;
    }

    /**
     * 
     * @param type $post
     * @param type $user
     * @param type $inputs
     * @return type
     */
    public function preparePostResponseWithRelations($post, $user, $inputs = []) {
        $data = $this->preparePostResponse($post, $user, $inputs);

        if ($post["post_type_id"] == config("general.post_type_index.product")) {
            $medias = $this->prepareMedia($post);
            $data["product"] = [
                "product" => $this->prepareProduct($post),
                "media" => $medias[0]
            ];

            $data["product"]["product"] = $this->prepareProduct($post);
        } else if ($post["post_type_id"] != config("general.post_type_index.api")) {
            $data["media_post"] = $this->prepareMediaPostAttributes($post);
            $data["media_post"]["media"] = $this->prepareMedia($post);
        }

        $data["boxes"] = $this->prepareBoxes($post["boxes"]);
        $data['comments'] = $this->prepareComments($post["comments"]);
        $data['likes'] = $this->prepareLikes($post["reactions"], $user);

        return $data;
    }

    /**
     * 
     * @param type $post
     * @param type $user
     * @param type $inputs
     * @return type
     */
    public function preparePostResponse($post, $user, $inputs = []) {
        $data = [
            "id" => $post["id"],
            "local_db_path" => !empty($post["local_db_path"]) ? $post["local_db_path"] : "",
            "content" => !empty($post["text_content"]) ? trim($post["text_content"]) : "",
            "is_liked" => !empty($post["reaction"]) ? true : false,
            "reaction_id" => !empty($post["reaction"]) ? (int) $post["reaction"]["reaction_id"] : 0,
            "like_count" => !empty($post["like_count"]) ? $post["like_count"] : 0,
            "comment_count" => !empty($post["comment_count"]) ? $post["comment_count"] : 0,
            "tag_count" => !empty($post["tag_count"]) ? $post["tag_count"] : 0,
            "short_url" => $this->prepareShortUrl($post),
            "short_code" => !empty($post["short_code"]) ? $post["short_code"] : "", // Temporarily added until removed from front-end code
            "is_bookmarked" => boolval($post["is_bookmarked"]),
            "post_type" => (int) $post["post_type"],
            "type" => (int) $post["post_type"], // Only user for is_fayved checking
            "entity_type" => ($post['post_type'] == config("general.post_type_index.product")) ? "PD" : "P",
            "categories" => [],
            "view_count" => 0,
            "user" => $this->prepareUser($post["user"])
        ];
        if (!$post['is_banner']) {
            $data['created_at'] = Carbon::parse($post["created_at"])->format("Y-m-d\TH:i:s\Z");
        }
        return $this->postPostAttributes($post, $data, $user, $inputs);
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    public function prepareMediaPostAttributes($post) {
        $response = [];
        $response["web_url"] = !empty($post["web_url"]) ? $post["web_url"] : "";
        $response['tag_count'] = $post["tag_count"];

        if (!empty($post["location_id"] > 0)) {
            $response['place'] = $this->prepareMediaPostPlace($post["place"], false);
        }
        return $response;
    }

    /**
     * Prepare post search attributes array
     * @param type $post
     * @param type $data
     * @param type $user
     * @param type $inputs
     * @return type
     */
    public function postPostAttributes($post, $data, $user, $inputs = []) {

        if ($post["post_type_id"] == config("general.post_type_index.api")) {
            if ($post["source_type"] == "google") {
                $data = $this->prepareGoogleAttributes($post, $data);
            } else {
                $data = $this->prepareApiAttributes($post, $data, $user);
            }
            // will call node js for post attributes
            $data = $this->prepareApiPostSubModule($data, $post, $inputs['ip']);
        }
        return $data;
    }

    /**
     * Prepare api attributes
     * @param type $post
     * @param type $data
     * @param type $user
     * @return boolean
     */
    public function prepareApiAttributes($post, $data, $user) {

        $item_type = config("general.item_type_by_number.{$post["api_attributes"]['item_type_number']}");
        $thumb_info = $this->getApiPostThumbInfo($post["source_type"], $post);
        $data["api_post"] = [
            "is_available" => true,
            "title" => "{$post["api_attributes"]['title']}",
            "thumb" => $thumb_info['thumb'],
            "bg_color" => $thumb_info['bg_color'],
            "is_blur" => !$thumb_info['thumb_status'],
            "item_type" => trans("messages.itemType" . ucfirst($item_type)),
            "item_type_number" => (int) $post["api_attributes"]['item_type_number'],
            "source_type" => "{$post["api_attributes"]['source_type']}",
            "source_id" => "{$post["api_attributes"]['source_id']}",
            "item_icon" => $this->prepareItemIconUrl($post["api_attributes"], "", $item_type),
            "source_icon" => config('g_map.source_icon_base_url') . $post["api_attributes"]['source_type'] . ".png",
            "is_fayved" => $post["api_attributes"]["is_fayved"],
            "fayvs" => (int) $post["api_attributes"]['fayvs'],
            "source_url" => $this->prepareSourceUrl($post["api_attributes"], $user),
            "post_description" => !empty($post["api_attributes"]['description']) ? strip_tags($post["api_attributes"]['description']) : "",
        ];
        return $data;
    }

    /**
     * Prepare google api attributes
     * @param type $post
     * @param type $data
     * @return type
     */
    public function prepareGoogleAttributes($post, $data) {

        $item_type = config("general.item_type_by_number.{$post['item_type_number']}");
        $thumb_info = $this->getApiPostThumbInfo($post["source_type"], $post);
        $data["api_post"] = [
            "is_available" => true,
            "title" => "{$post["api_attributes"]['location_name']}",
            "thumb" => $thumb_info['thumb'],
            "bg_color" => $thumb_info['bg_color'],
            "is_blur" => !$thumb_info['thumb_status'],
            "item_type" => trans("messages.itemType" . ucfirst($item_type)),
            "item_type_number" => (int) $post['item_type_number'],
            "source_type" => "google",
            "source_id" => "{$post["api_attributes"]['fs_location_id']}",
            "item_icon" => $this->prepareItemIconUrl("google", $post["api_attributes"]['location_type'], $item_type),
            "source_icon" => config('g_map.source_icon_base_url') . "google.png",
            "is_fayved" => $post["api_attributes"]["is_fayved"],
            "fayvs" => (int) $post["api_attributes"]['fayvs'],
            "source_url" => "", // Not use in case of google post confirmed from @Shahzad Rehamt Aug 12, 2021
            "post_description" => !empty($post["api_attributes"]['caption']) ? strip_tags($post["api_attributes"]['caption']) : "",
        ];
        return $data;
    }

    private function getApiPostThumbInfo($source_type = "imdb", $post = null) {
        $thumb = [];
        $attr = $post["api_attributes"];
        $thumb_status = isset($attr["thumb_status"]) && $attr["thumb_status"] ? true : false;

        if ($source_type == "google" && !$thumb_status) {
            $thumb = [
                "thumb_status" => false,
                "thumb" => $this->prepareMapScreenPath($attr),
                "bg_color" => config('g_map.map_default_bg_color'),
            ];
        } else {
            $source_id = ($source_type == "google") ? $attr["fs_location_id"] : $attr["source_id"];
            $thumbnail = !empty($attr["thumbnail"]) ? "{$attr["thumbnail"]}" : $this->getPreviewCdn($source_id, $source_type, $attr["item_type_number"]);
            //sending default bg color
            $thumb = [
                "thumb_status" => boolval($attr["thumb_status"]),
                "thumb" => "{$thumbnail}",
                "bg_color" => "#" . str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT),
            ];

            $thumb["bg_color"] = $this->getBgColor($attr["bg_color"]);
        }
        return $thumb;
    }

    public function prepareComments($comments) {
        $response = [];
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $response[] = $this->prepareCommentObject($comment);
            }
        }
        return $response;
    }

    /*
     * 
     */

    private function prepareCommentObject($comment) {
        return [
            'id' => (int) $comment["id"],
            'comment' => (string) $comment["comment"],
            'created_at' => $this->parseCarbonFormat($comment["created_at"]),
            'user' => $this->prepareUser($comment["user"])
        ];
    }

    /**
     * Prepare user likes
     * @param type $reactions
     * @param type $user
     * @return type
     */
    public function prepareLikes($reactions, $user) {
        $response = [];
        if (isset($reactions)) {
            foreach ($reactions as $value) {
                $response[] = [
                    "id" => (int) $value['id'],
                    "reaction_id" => (int) $value['reaction_id'],
                    "created_at" => $this->parseCarbonFormat($value["created_at"]),
                    "user" => $this->prepareUser($value['user'])
                ];
            }
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
        $location["id"] = $place["fs_location_id"];
        $location["name"] = $place["location_name"];
        $location["types"] = $this->processTags($place["tags"]);
        $location["lat"] = !empty($place["latitude"]) ? (string) $place["latitude"] : "";
        $location["lon"] = !empty($place["longitude"]) ? (string) $place["longitude"] : "";
        $location["category"] = ""; // Currently ML Team working on it
        if ($isLocationPost) {
            $location["map_screen"] = $this->prepareMapScreenPath($place);
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

        if ($post['item_type_number'] != 5 && !$post["api_attributes"]['is_processed']) {
            $client = new \App\Helpers\GuzzleHelper();
            $item_type_number = $post['item_type_number'];
            $post_id = $post["id"];
            $preview = $client->getApiPostRelatedData($post_id, $post["source_type"], $post["source_id"], $item_type_number, $ip);

            $data["api_post"]["is_available"] = ($post["source_type"] != "web" && empty($preview)) ? false : true;
            $data["api_post"] += $this->prepareAPIResponse((object) $post["api_attributes"], $preview);
        } else {
            $data["api_post"]["is_available"] = boolval($post["api_attributes"]['is_available']);
            $data["api_post"] += $this->prepareAPIResponse((object) $post["api_attributes"], null);
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
            $uid = base64_encode($user["uid"]);
            return $analytic_server . "?source_type={$post['source_type']}&source_id={$post['source_id']}&item_type_number={$post['item_type_number']}&i={$uid}";
        } else if (isset($post['external_url']) && !empty($post['external_url'])) {
            return $post['external_url'];
        }
        return "";
    }

    public function prepareMedia($post) {
        $medias = [];
        foreach ($post["media"] as $post_media) {
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
     * @param type $user
     */
    private function prepareUser($user) {
        if ($user["picture"] == "profile_default.jpg" && $user["gender"] == "female") {
            $user["picture"] = config("image.default_pictures_by_gender.female");
        }
        $pic_url = config("image.user_thumb_base_url") . "{$user["picture"]}";
        return [
            "id" => "{$user["uid"]}",
            "username" => "{$user["username"]}",
            "full_name" => "{$user["full_name"]}",
            "picture" => "{$pic_url}",
            "is_private" => !boolval($user["is_live"]),
            "is_verified" => boolval($user["is_verified"]),
            "follow_status" => isset($user["is_followed"]) && in_array($user["is_followed"], ["A", "P"]) ? "{$user["is_followed"]}" : "",
        ];
    }

    /**
     * NOT IN USE NOW
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
     * @param type $boxes
     * @return array
     */
    public function prepareBoxes($boxes) {
        $resp = [];
        foreach ($boxes as $box) {
            $medias = [];
            $box_arr = ["id" => $box["id"], "name" => $box["name"], "status" => $box["status"], 'media' => $medias];
            array_push($resp, $box_arr);
        }
        return $resp;
    }

    public function extractWidthHeightImages($post_media) {
        \Log::info($post_media);
        $media = array();
        //For older API
        $media['media_type'] = (int) $post_media["file_type_number"];
        //For New API
        $media['type'] = (int) $post_media["file_type_number"];
        switch ($post_media["file_type_number"]) {
            case 1:
                $media['actual'] = $post_media["original"];
                $media['thumbnail'] = isset($post_media["home"]) ? $post_media["home"] : $post_media["medium"];
                break;
            case 2:
                $media['thumbnail'] = isset($post_media["home"]) ? $post_media["home"] : $post_media["medium"];
                $media['actual'] = $this->getBaseVideoUrl() . $post_media["file_base_name"] . ".mp4";
                break;
            default:
                "";
        }
        $media['width'] = !empty($post_media["medium_file_width"]) ? (int) $post_media["medium_file_width"] : (int) $post_media["file_width"];
        $media['height'] = !empty($post_media["medium_file_height"]) ? (int) $post_media["medium_file_height"] : (int) $post_media - ["file_height"];
        return $media;
    }

    /**
     * it will make the base url of cdn 
     * http://d1lkqsdr30qepu.cloudfront.net/staging4/posts/videos/source/
     * @return type
     */
    private function getBaseVideoUrl() {
        return config("image.fayvo-sample-cdn-url") . config("image.ENV_FOLDER") . config("image.POST_FOLDER") . config("image.post_videos_source");
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    private function prepareShortUrl($post) {
        $short_url = "";
        if (config("general.base_app_version") >= 4.4) {
            if ($post["post_type_id"] == config("general.post_type_index.search")) {
                $source = $post["source_type"] . "-" . $post["source_id"] . "@" . $post["item_type_number"];
                $item_type = config("general.item_type_by_number.{$post["item_type_number"]}");
                $short_url = $this->getShortUrl(['id' => $post["id"], 'source_key' => $source], strtolower($item_type), "P");
            } else {
                $short_url = $this->getShortUrl(['id' => $post["id"], 'source_key' => $post["id"]], "media", "P");
            }
        } else {
            $env = env("APP_ENV") == 'production' ? 'p' : 's';
            $short_url = !empty($post["short_code"]) ? config("g_map.fayvo_share_base_url") . 't=pt&fsq=' . $post["short_code"] . '&en=' . $env : "";
        }
        return $short_url;
    }

    /**
     * Prepare product model
     * @param type $response
     * @param type $post_object
     * @return type
     */
    public function prepareProduct($post = []) {
        return [
            "title" => "{$post["title"]}",
            "description" => "{$post["text_content"]}",
            "price_unit" => "{$post["price_unit"]}",
            "price" => $post["price"],
            "shop_url" => "{$post["web_url"]}",
        ];
    }

}
