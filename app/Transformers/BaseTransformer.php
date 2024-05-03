<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Carbon\Carbon;

class BaseTransformer extends TransformerAbstract {

    use \App\Traits\BucketPathTrait;

    protected $s3Url = null;

    /**
     * get user profile image URL
     * @param type $image
     * @param type $type
     * @return string
     */
    public function getUserProfileImage($image, $type = 'original', $bucket = 'fayvo-sample') {
        $picture = "";
        $this->s3Url = config("image.$bucket-cdn-url") . config("image.ENV_FOLDER") . config("image.USER_FOLDER");
        if (!empty($image)) {
            $picture = $this->getProfileImageUrl($image, $bucket, $type);
        }
        return $picture;
    }

    private function getProfileImageUrl($image, $bucket, $type) {
        $picture = "";
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }
        switch (strtolower($type)) {
            case 'original':
                $picture = $this->s3Url . config('image.profile_original') . $image;
                break;
            case 'medium':
                $picture = $this->s3Url . config('image.profile_medium') . $image;
                break;
            default:
                $picture = $this->s3Url . config('image.profile_thumb') . $image;
                break;
        }
        return $picture;
    }

    public function preparePostMedia($media = []) {
        $response = [];
        if (!empty($media)) {
            $s3Url = config('image.s3_url');
            $response = $this->preparePostMediaUrls($media, $s3Url);
            $response['width'] = !empty($media->medium_file_width) ? (int) $media->medium_file_width : (int) $media->file_width;
            $response['height'] = !empty($media->medium_file_height) ? (int) $media->medium_file_height : (int) $media->file_height;
        }
        return $response;
    }

    private function preparePostMediaUrls($media, $s3Url) {
        $response = [];
        if ($media->file_type_number == 1) {
            $response['actual'] = $s3Url . config('image.post_original_image_url') . $media->file;
            $response['thumbnail'] = $s3Url . config('image.post_img_medium_url') . $media->file;
        } else if ($media->file_type_number == 2) {
            $response['actual'] = $s3Url . config('image.post_video_url') . $media->file;
            $response['thumbnail'] = $s3Url . config('image.post_video_url') . $media->file_base_name . '.jpg';
        }
        return $response;
    }

    public function preparePostUser($user = [], $isImage = false, $isLikedUser = false) {
        $response = [];
        if (!empty($user)) {
            $response['id'] = $user->uid;
            $response['username'] = $user->username;
            $response['full_name'] = $user->full_name;
            if ($isImage) {
                $response['picture'] = $this->getUserProfileImage($user->picture, 'thumb');
            }
            if ($isLikedUser) {
                $response['is_private'] = (bool) $user->is_private;
                $response['follow_status'] = !empty($user->isFriend) ? $user->isFriend->status : "";
            }
        }
        return $response;
    }

    public function preparePostBox($box = []) {
        $response = [];
        if (!empty($box)) {
            $response['id'] = $box->id;
            $response['name'] = $box->name;
        }
        return $response;
    }

    /**
     * prepare post comment array
     * @param type $comment
     */
    public function preparePostComment($comment = []) {
        $response = [];
        if (!empty($comment)) {
            $response = [
                'id' => (int) $comment->id,
                'comment' => $comment->comment,
                'created_at' => $comment->created_at,
                'username' => $comment->user->username,
                'picture' => $this->getUserProfileImage($comment->user->picture, 'thumb'),
                'user_id' => (string) $comment->user->uid,
                'post_id' => (int) $comment->user->user_post_id
            ];
            foreach ($comment->commentMention as $key => $user) {
                $response['mention_users'][$key] = $this->preparePostUser($user->user, false);
            }
        }
        return $response;
    }

    /**
     * parse carbon format
     * @param type $datatime
     * @return type
     */
    public function parseCarbonFormat($datatime) {
        return Carbon::parse($datatime)->format("Y-m-d\TH:i:s\Z");
    }

    public function getMediaFilePath($post_file, $post_type, $image_type = 'thumb') {
        $file = "";
        $s3Url = config('image.s3_url');
        switch ($post_type) {
            case 'P':
                $file = $this->getSelectedMediaPath($s3Url, $image_type, $post_file);
                break;
            case 'V':
                $video_name = explode('.', $post_file);
                $file = config('image.s3_url') . config('image.post_video_thumbnail_url') . $video_name[0] . '.jpg';
                break;
            default:
                break;
        }
        return $file;
    }

    private function getSelectedMediaPath($s3Url, $type, $post_file) {
        $link = "";
        $type = strtolower($type);
        switch ($type) {
            case 'thumb':
                $link = $s3Url . config('image.post_thumb1_url') . $post_file;
                break;
            case 'medium':
                $link = $s3Url . config('image.post_img_medium_url') . $post_file;
                break;
            default:
                break;
        }
        return $link;
    }

    /**
     * Prepare search post content arespone rray
     * @param type $post
     * @return type
     */
    public function prepareSearchPostArr($post) {
        $response = (new \stdClass());

        try {
            if (!empty($post)) {
                $itemType = !empty($post->item_type) ? (string) ucfirst(strtolower($post->item_type)) : "Other";
                $itemType = trans("messages.itemType$itemType");
                $response = [];
                $response["is_fayved"] = false;
                $response["title"] = (string) $post->title;
                $response["source_type"] = !empty($post->source_type) ? (string) $post->source_type : "";
                $response["source_id"] = !empty($post->source_id) ? (string) $post->source_id : "";
                $response["source_link"] = !empty($post->source_link) ? (string) $post->source_link : "";
                $response["thumbnail"] = !empty($post->thumbnail) ? (string) $post->thumbnail : "";
                $response["bg_image"] = !empty($post->bg_image) ? (string) $post->bg_image : "";
                $response["item_type"] = $itemType;
                $response["rating"] = (string) $post->rating;
                $response["item_type_number"] = $post->item_type_number > 0 ? (int) $post->item_type_number : (int) 0;
                $response["item_icon"] = config("general.item_type_icons." . strtolower($post->item_type));
                if (in_array(strtolower($post->item_type), ["place", "google", "food", "location"])) {
                    $response["map_screen"] = $this->prepareMapScreenPath($post->postable->toArray());
                    $response["item_icon"] = $this->prepareItemIconUrl($post->source_type, $post->postable->location_type, $post->item_type);
                }
            }
        } catch (\Exception $ex) {
            $response = (new \stdClass());
        }
        return $response;
    }

    /**
     * 
     * @param type $place
     * @return string
     */
    protected function prepareMapScreenPath($place) {
        if ((isset($place["map_screen"]) && $place["map_screen"]) || (isset($place["map_image"]) && $place["map_image"])) {
            return config("image.fayvo-sample-cdn-url") . "production/place/" . $place["fs_location_id"] . ".png";
        } else {
            $cdn = config('image.fv-cdn-url');
            $query = "image/map?place_id={$place["fs_location_id"]}&size=600x300&lat={$place["latitude"]}&lon={$place["longitude"]}";
            $url = "{$cdn}{$query}";
            return $url;
        }
    }

    /**
     * Prepare google map static
     * @param type $place
     * @param string $lat_lon
     * @param type $post_id
     * @return string
     */
    public function prepareDiscoverGoogleMapUrl($place = "", $lat_lon = [], $post_id = 0) {
        $url = "";
        if (isset($place["400_600"]) && $place["400_600"]) {
            $url = config("image.fayvo-sample-cdn-url") . "production/place/" . $place["fs_location_id"] . "_400_600.png?ver=2";
        } else {
            $cdn = config('image.fv-cdn-url');
            $query = "image/map?place_id={$place["fs_location_id"]}&size=400x600&lat={$lat_lon["lat"]}&lon={$lat_lon["lon"]}";
            $url = "{$cdn}{$query}";
        }
        return $url;
    }

    /**
     * Prepare item icon url
     * @param type $post
     * @return string
     */
    public function prepareItemIconUrl($source_type = "google", $caption = "", $itemType = "") {
        return config("general.item_type_icons." . strtolower($itemType));

//        if ($source_type == config("general.post_source_types.google")) {
//            $location_type = !empty($caption) ? strtolower($caption) : "";
//            $location_type = "";
//            $icon = !empty(config("g_map.icons_mapping.$location_type")) ? config("g_map.icons_mapping.$location_type") : config('g_map.default_icon_name');
//            $url = config('g_map.item_icon_base_url') . "$icon.png";
//        }
//        return $url;
    }

    /**
     * 
     * @param type $str
     * @return type
     */
    protected function arabicW2e($str) {
        if (app('translator')->getLocale() == "ar") {
            $arabic_eastern = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
            $arabic_western = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
            return str_replace($arabic_western, $arabic_eastern, $str);
        }
        return $str;
    }

    /**
     * 
     * @param type $db_object
     * @return string
     */
    protected function getShortUrl($db_object, $item_type = 'place', $type = "A") {
        $short_url = "";
        $source = base64_encode($db_object['source_key']);
        $base_url = config("g_map.api_share_base_url_" . env("APP_ENV"));
        $query = config("g_map.api_share_prefix.{$item_type}") . "{$db_object['id']}&type={$type}&src={$source}";
        $short_url = "{$base_url}{$query}";
        return $short_url;
    }

    public function getPreviewCdn($source_id = "", $source_type, $item_type_number = 0, $size = "original") {
        $url = "";
        if (!empty($source_id)) {
            $cdn = config("image.fv-cdn-url");
            $query = "source_id={$source_id}&source_type={$source_type}&item_type_number={$item_type_number}&size={$size}";
            $url = "{$cdn}cdn/source?{$query}";
        }
        return $url;
    }

    protected function getRandomColor() {
        $color = "#" . str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT);
        return $color;
    }

    public function getAnalyticalUrl($uid = 0, $attr = []) {
        if ($analytic_server = config("general.analytics_server")) {
            $uid = base64_encode($uid);
            return $analytic_server . "?source_type={$attr["source_type"]}&source_id={$attr["source_id"]}&item_type_number={$attr["item_type_number"]}&i={$uid}";
        } else {
            return "";
        }
    }

    /**
     * Search trailer from trailers
     * @param type $source
     * @return string
     */
    public function searchYoutuveTrailerUrl($source = []) {
        try {
            $trailer_url = "";
            if (isset($source["trailers"]) && !empty($source["trailers"])) {
                $trailers = json_decode($source["trailers"], true);
                foreach ($trailers as $key => $trailer) {
                    if ($trailer["media_type"] == 1) {
                        $trailer_url = $trailer["key"];
                        break;
                    }
                }
            }

            return $trailer_url;
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = $ex->getMessage();
            $log["line"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return "";
        }
    }

    protected function isDecimal($val) {
        return is_numeric($val) && floor($val) != $val;
    }

    protected function roundRating($rating = "") {
        if (!empty($rating) && $rating > 0) {
            if ($this->isDecimal($rating)) {
                $rating = round($rating, 2);
            }
            return $rating;
        } else {
            return 0;
        }
    }

    protected function getBgColor($bg_color) {
        $color = $this->getRandomColor();
    
        if (!empty($bg_color) && strlen($bg_color) == 7) {
            $color = $bg_color;
        }
        return $color;
    }

    /**
     * Get original actual or play url
     * @param type $media
     * @return type
     */
    protected function getActualUrl($media = []) {
        $actual_url = "";
        if ($media['file_type_number'] == 2) {
            $actual_url = $this->getMediaPlayUrl($media["file"]);
        } else {
            $actual_url = isset($media["original"]) ? "{$media["original"]}" : $this->getMediaOriginalUrl($media["file"]);
        }
        return $actual_url;
    }

}
