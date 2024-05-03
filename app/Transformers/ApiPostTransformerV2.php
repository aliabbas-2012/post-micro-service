<?php

namespace App\Transformers;

use App\Helpers\GuzzleHelper;
use App\Models\UserPost;
use Carbon\Carbon;

/**
 * Post Transformer
 * @author ali
 */
class ApiPostTransformerV2 extends BaseTransformer {

    use \App\Traits\ApiPostTransformerTrait;

    /**
     * 
     * @var type 
     */
    public $fayvedBuilder;

    /** In use
     * It will only contains post and its first media URL
     * @param type $arr
     * @return type
     */
    public function transform($arr) {
        $user = $arr["u"];
        $api = $arr["a"];
        $device_type = $arr["d"];
        $inputs = $arr["inputs"];
        return $this->prepareAPIEntityResponse($api, $user, $device_type, $inputs);
    }

    /**
     * 
     * @param type $post
     * @param type $device
     * @param type $ip
     * @return type
     */
    public function preparePostResponseWithRelations($post, $device, $inputs = []) {


        $data = $this->preparePostResponse($post);
        if ($post->post_type_id == config('general.post_type_index.api')) {
            $data['api_post'] = $this->prepareApiPost($post, $device, $inputs);
        } else {
            $data["media_post"] = $this->prepareMediaPost($post);
        }
        $data["boxes"] = $this->prepareBoxes($post);
        $data["categories"] = [];
        return $data;
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    public function preparePostResponse($post) {

        $data = [
            "id" => $post->id,
            "type" => (int) $post->post_type_id,
            "post_type" => $post->post_type_id,
            "entity_type" => "P",
            "user" => $this->prepareUser($post->user, 'original'),
            "content" => !empty($post->text_content) ? trim($post->text_content) : "",
            "local_db_path" => !empty($post->local_db_path) ? $post->local_db_path : "",
            "view_count" => 0,
            "like_count" => !empty($post->postTotalLikes) ? $post->postTotalLikes->total_likes : 0,
            "comment_count" => !empty($post->postTotalComments) ? $post->postTotalComments->total_comments : 0,
            "is_liked" => false,
            "reaction_id" => 0,
            "created_at" => $post->created_at->format("Y-m-d\TH:i:s\Z"),
            "short_url" => $this->prepareShortUrl($post),
            "short_code" => !empty($post->short_code) ? $post->short_code : "",
            "is_bookmarked" => false,
            "likes" => !empty($post->likes) ? $this->prepareLatestLikes($post->likes) : [],
            "comments" => [],
        ];
        return $data;
    }

    /**
     * In use
     * @param type $api
     * @return type
     */
    public function prepareAPIEntityResponse($api, $user, $device_type = "ios", $inputs = []) {
        //create dummy object so next method should work fine
        $post = new UserPost();
        $post->forceFill([
            "api" => $api,
            "source_type" => ($api->item_type_number == 3 || $api->item_type_number == 4) ? "google" : $api->source_type,
            "source_id" => ($api->item_type_number == 3 || $api->item_type_number == 4) ? $api->fs_location_id : $api->source_id,
            "item_type_number" => $api->item_type_number,
            "post_type_id" => 7,
            "id" => $api->id
        ]);
        $data = [
            "id" => $api->id,
            "type" => (int) $post->post_type_id,
            "post_type" => $post->post_type_id,
            "entity_type" => "A",
            "content" => "",
            "local_db_path" => $api->source_key,
            "view_count" => 0,
            "like_count" => 0,
            "comment_count" => 0,
            "is_liked" => false,
            "reaction_id" => 0,
            "created_at" => Carbon::parse($api->created_at)->format("Y-m-d\TH:i:s\Z"),
            "short_url" => $this->prepareShortUrl($post, "A"),
            "short_code" => "",
            "is_bookmarked" => false,
            "likes" => [],
            "comments" => [],
        ];
        $data['api_post'] = $this->prepareApiPost($post, $user, $device_type, $inputs);
        return $data;
    }

    /**
     * Prepare Media post
     * @param type $post
     * @return type
     */
    public function prepareMediaPost($post) {
        $response = [];
        $response["web_url"] = !empty($post->web_url) ? $post->web_url : "";
        $response["tag_count"] = !empty($post->postTotalTags) ? $post->postTotalTags->total_tags : 0;
        if (!empty($post->place)) {
            $response["place"] = $this->preparePlace($post->place);
        }
        $response["media"] = $this->prepareMedia($post);

        return $response;
    }

    /** in use
     * method to prepare post detail payload of search type of new response
     * @param type $post
     * @param type $device
     * @return type
     */
    public function prepareApiPost($post, $user, $device_type = "ios", $inputs = []) {
        $response = [];
        try {
            $title = $post->item_type_number == 3 || $post->item_type_number == 4 ? $post->api->location_name : $post->api->title;
            $item_type = config("general.item_type_by_number.{$post->item_type_number}");
            $thumb_info = $this->getApiPostThumbInfo($post);
            $translated_item_type = trans("messages.itemType" . ucfirst($item_type));
            $fayv_info = $this->fayvedBuilder->getFayvedCount($user->id, $post, 0);
            $response += [
                "is_available" => true,
                "is_fayved" => boolval($fayv_info["is_fayved"]),
                "fayvs" => (int) $fayv_info["fayvs"],
                "title" => $title,
                "source_type" => $post->source_type,
                "source_url" => $this->prepareSourceUrl($post, $user),
                "source_id" => $post->source_id,
                "item_type_number" => (int) $post->item_type_number,
                "item_type" => $translated_item_type,
                "source_icon" => config('g_map.source_icon_base_url') . $post->source_type . ".png",
                "item_icon" => config("general.item_type_icons.{$item_type}"),
                "post_description" => !empty($post->api->description) ? strip_tags($post->api->description) : "",
                "is_blur" => !$thumb_info['thumb_status'],
                "thumb" => $thumb_info['thumb'],
                "bg_color" => $thumb_info['bg_color'],
                "scheme_url" => $this->prepareSchemeUrl($user, $post, $device_type),
                "external_url" => $this->getAnalyticalUrl($user->uid, ["item_type_number" => $post->item_type_number, "source_id" => $post->source_id, "source_type" => $post->source_type])
            ];
            if ($key = $this->isUpdateIconRequired($post->item_type_number, $inputs)) {
                $response['item_icon'] = config("general.item_type_icons.{$key}");
            }
//                $post->api->is_processed = false;
            if ($post->item_type_number != 5 && !$post->api->is_processed) {
                $obj = new GuzzleHelper();
                $preview = $obj->getApiPostRelatedData($post->id, $post->source_type, $post->source_id, $post->item_type_number);
                $preview = !empty($preview) ? $preview : null;
                $response["is_available"] = ($post->source_type != "web" & $preview == null) ? false : true;
                $response += $this->prepareAPIResponse($post->api, $preview);
            } else {
                $response["is_available"] = boolval($post->api->is_available);
                $response += $this->prepareAPIResponse($post->api, null);
            }
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            $response = (new \stdClass());
        }
        return $response;
    }

    /**
     * In use
     * It will create a dynamic source url which 
     * will be navigated from our own analytics server 
     * and track the record
     * @param type $post
     * @param type $device
     * @return type
     */
    private function prepareSourceUrl($post, $user) {
        $api = $post->api;
        if ($analytic_server = config("general.analytics_server")) {
            $source_id = $post->source_type == "web" ? base64_encode($api->external_url) : $post->source_id;
            $uid = base64_encode($user->uid);
            return $analytic_server . "?source_type={$post->source_type}&source_id={$source_id}&item_type_number={$post->item_type_number}&i={$uid}";
        }
        return isset($api->source_link) ? $api->source_link : "";
    }

    /**
     * prepare latest like method
     * @param type $likes
     * @return array
     */
    public function prepareLatestLikes($likes) {
        $response = [];
        if (!empty($likes)) {
            foreach ($likes as $like) {
                $response[] = [
                    "id" => $like->id,
                    "reaction_id" => (int) $like->reaction_id,
                    "created_at" => $this->parseCarbon($like->created_at),
                    "user" => $this->prepareUser($like->user, 'thumb'),
                ];
            }
        }
        return $response;
    }

    /**
     * prepare latest comment method
     * @param type $comments
     * @return array
     */
    public function prepareLatestComments($comments) {
        $response = [];
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $response[] = [
                    "id" => $comment->id,
                    "comment" => $comment->comment,
                    "created_at" => $this->parseCarbon($comment->created_at),
                    "user" => $this->prepareUser($comment->user, 'thumb'),
                ];
            }
        }
        return $response;
    }

    /**
     * 
     * @param type $place
     * @return type
     */
    public function preparePlace($place) {
        try {

            return [
                "id" => $place->fs_location_id,
                "name" => $place->location_name,
                "types" => ucfirst(str_replace("_", " ", $place->location_type)),
                "map_screen" => $this->prepareMapScreenPath($place),
                "rating" => (String) $this->arabicW2e($place->rating . "/10"),
                "lat" => $place->latitude,
                "lon" => $place->longitude,
                "category" => ""
            ];
        } catch (\Exception $ex) {
            return;
        }
    }

    /**
     * prepare user 
     * @param type $user
     * @return type
     */
    public function prepareUser($user, $type = 'original') {
        return [
            "id" => $user->uid,
            "username" => $user->username,
            "full_name" => $user->full_name,
            "picture" => $this->getUserProfileImage($user->thumb, $type),
            "is_private" => boolval($user->is_private),
            "is_verified" => boolval($user->is_verified),
            "follow_status" => ""
        ];
    }

    /**
     * 
     * @param type $post
     * @param type $entity_type
     * @return string
     */
    public function prepareShortUrl($post, $entity_type = "P") {
        $short_url = "";
        if (config("general.base_app_version") >= 4.4) {
            if ($post->post_type_id == config("general.post_type_index.api")) {
                $source = "{$post->source_type}-{$post->source_id}@{$post->item_type_number}";

                $item_type = config("general.item_type_by_number.{$post->item_type_number}");

                $short_url = $this->getShortUrl(['id' => $post->id, 'source_key' => $source], $item_type, $entity_type);
            } else {
                $short_url = $this->getShortUrl(['id' => $post->id, 'source_key' => $post->id], "media", $entity_type);
            }
        } else {
            $en = env("APP_ENV") == "production" ? "p" : "s";
            if (!empty($post->short_code)) {
                $short_url = config('general.fayvo_share_url') . "t=pt&en=$en&fsq={$post->short_code}";
            }
        }
        return $short_url;
    }

    /**
     * Prepare post boxes response array
     * @param type $post
     * @return array
     */
    public function prepareBoxes($post) {
        $boxes = [];
        foreach ($post->postBoxes as $postBox) {
            $box = $postBox->box;
            array_push($boxes, ["id" => $box->id, "name" => $box->name, "status" => $box->status,
                "media" => []]);
        }
        return $boxes;
    }

    /**
     * prepareMedia method
     * @param type $post
     * @return array
     */
    public function prepareBoxMedia($post) {
        $medias = [];
        foreach ($post->postMedia as $post_media) {
            array_push($medias, $this->extractBoxMedia($post_media));
        }
        return $medias;
    }

    /**
     * prepareMedia method
     * @param type $post
     * @return array
     */
    public function prepareMedia($post) {
        $medias = [];

        foreach ($post->postMedia as $post_media) {
            array_push($medias, $this->extractWidthHeightImages($post_media));
        }
        return $medias;
    }

    public function extractWidthHeightImages($post_media) {
        $media = array();
//For New API
        $media['type'] = $post_media->file_type_number;
//For older API
        $media['media_type'] = $post_media->file_type_number;
        switch ($post_media->file_type_number) {
            case 1:
                $media['actual'] = $post_media->original;
                $media['thumbnail'] = $post_media->medium;
                break;
            case 2:
                $media['thumbnail'] = $post_media->medium;
                $media['actual'] = $post_media->source;
                break;
            default:
                "";
        }
        $media['width'] = !empty($post_media->medium_file_width) ? (int) $post_media->medium_file_width : (int) $post_media->file_width;
        $media['height'] = !empty($post_media->medium_file_height) ? (int) $post_media->medium_file_height : (int) $post_media->file_height;
        $media['bg_color'] = !empty($post_media->bg_color) ? $post_media->bg_color : config('general.default_bg_color');

        return $media;
    }

    public function extractBoxMedia($post_media) {
        $media = array();
        switch ($post_media->file_type_number) {
            case 1:
                $media['thumb'] = $post_media->thumb;
                break;
            case 2:
                $media['thumb'] = $post_media->thumb;
                break;
            default:
                "";
        }
        return $media;
    }

    /** In use
     * Prepare scheme url
     * @param type $device
     * @param type $post
     *      SearchPost
     * @return type
     */
    private function prepareSchemeUrl($user, $post, $device_type = "ios") {
        $source_link = "";
        $source_type = $post->source_type;
        if ($source_type == "imdb") {
            $imdb_id = $post->source_id;

            if (!empty($post->api->session_id)) {
                $imdb_id = $post->api->session_id;
                $source_link = str_replace(array("http://", "https://"), "", "{$imdb_id}");
            }
        } else if ($source_type == "youtube") {
            $source_link = "www.youtube.com/watch?v={$post->source_id}";
        } else {
            $source_link = !empty($post->source_link) ? strip_tags(str_replace(array("http://", "https://"), "", $post->source_link)) : "";
        }
        if (!empty($source_link)) {
            return config('g_map.scheme_url.' . $device_type . ".{$source_type}") . $source_link;
        }
        return "";
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    private function getApiPostThumbInfo($post) {
        $thumb = [];
        $attr = $post->api;

        if ($post->source_type == "google" && !$attr->thumb_status) {
            $thumb = [
                "thumb_status" => false,
                "thumb" => $this->prepareMapScreenPath((array) $attr),
                "bg_color" => config('g_map.map_default_bg_color'),
            ];
        } else {
            $thumbnail = !empty($attr->thumbnail) ? $attr->thumbnail : $this->getPreviewCdn($post->source_id, $post->source_type, $post->item_type_number);
            $thumb = [
                "thumb_status" => boolval($attr->thumb_status),
                "thumb" => "{$thumbnail}",
                "bg_color" => $this->getRandomColor()
            ];
            if (isset($attr->bg_color) && !empty($attr->bg_color)) {
                $thumb["bg_color"] = $this->getBgColor($attr->bg_color);
            } else if (isset($attr->postMedia) && !empty($post->postMedia[0])) {
                $thumb["bg_color"] = $this->getBgColor($post->postMedia[0]->bg_color);
            }
        }
        return $thumb;
    }

    /**
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
