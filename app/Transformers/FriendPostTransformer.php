<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Transformers;

use App\Helpers\EsQueries\TempMediaUrl;

/**
 * Description of TrendingTransformer
 *
 * @author rizwan
 */
class FriendPostTransformer extends BaseTransformer {

    public function transform($post) {
        $response = [];
        $response = $this->prepareCommonPostArr($post['post']);
        // For backend dev testing/verification
        $response['created_location'] = isset($post['post']['created_location']) ? $post['post']['created_location'] : [];
        $response['user'] = $this->prepareDiscoverPostUser($post['user']);
        if (in_array($response['post_type'], config("general.search_posts"))) {
            $response = $this->prepareApiPost($response, $post['post']);
        } else {
            $response = $this->prepareMediaPost($response, $post['post']);
        }
        return $response;
    }

    /**
     * Prepare common post
     * @param type $post
     * @return type
     */
    public function prepareCommonPostArr($post) {
        $response = [];
        $response['id'] = $post['id'];
        $response['type'] = (int) $post['post_type_id'];
        $response['entity_type'] = "P";
        $response['created_at'] = $post['created_at'];
        $response = $this->prepareDiscoverMedia($response, $post);
        $response['post_type'] = (int) $post['post_type_id'];
        $response['is_liked'] = false;
        $response['reaction_id'] = 0;
        $response['short_code'] = (isset($post['short_code']) && !empty($post['short_code'])) ? $post['short_code'] : "";
        $response['likes'] = 0;
        $response['comments'] = 0;
        $response['views'] = 0;
        return $response;
    }

    public function prepareMediaPost($response, $post) {
        $response['media_attributes'] = [
            'likes' => 0,
            'comments' => 0,
            'views' => 0,
//            'medias' => $this->preparePostMediasArrays($post['post_media']),
            'medias' => []
        ];
        return $response;
    }

    public function prepareApiPost($response, $post_object = []) {
        $post = $post_object['post_attributes'];
        $caption = isset($post["caption"]) ? $post["caption"] : "";
        if ($post['source_type'] == config('general.post_source_types.google')) {
            $response = $this->prepareGoogleMapThumbs($response, $post['thumb_status'], $post_object);
            $response["place"] = [
                "lat" => (string) $post_object["post_location"]["lat"],
                "lon" => (string) $post_object["post_location"]["lon"]
            ];
        }
        $response['api_attributes'] = [
            'fayvs' => $this->getFayvCount($post_object),
            'title' => $post['title'],
            'caption' => $this->getApiPostCaption($post['source_type'], $post),
            'source_url' => $this->extractApiPostSourceUrl($post),
            'source_id' => $post['source_id'],
            'source_type' => $post['source_type'],
            'item_type' => trans("messages.itemType" . ucfirst(strtolower($post['item_type']))),
//            'trailers' => $this->extractApiPostTrailers($post),
            'trailers' => [], // Commented after discussion with @asif @ali April 13,2020
            'is_fayved' => false,
            'item_type_number' => (int) $post['item_type_number'],
            'item_icon' => $this->prepareItemIconUrl($post['source_type'], $caption, $post['item_type']),
            'source_icon' => config('g_map.source_icon_base_url') . $post['source_type'] . ".png",
        ];
        return $response;
    }

    /**
     * Prepare trending user array
     * @param type $user
     * @return type
     */
    public function prepareDiscoverPostUser($user = []) {
        $user['bucket'] = !empty($user['bucket']) ? $user['bucket'] : "fayvo-sample";
        $baseUrl = config('image.' . $user['bucket'] . '-cdn-url') . config("image.ENV_FOLDER") . config("image.USER_FOLDER");
        $response = [];
        $response['id'] = $user['uid'];
        $response['username'] = $user['username'];
        $response['full_name'] = $user['full_name'];
        $response['is_private'] = !(bool) $user['is_live'];
        $response['picture'] = $baseUrl . config("image.profile_thumb") . $user['picture'];
        return $response;
    }

    /**
     * Prepare post all medias array
     * @param type $medias
     * @return type
     */
    public function preparePostMediasArrays($medias = []) {
        $response = [];
        $client = $this->getUrlClient();
        foreach ($medias as $key => $media) {
            $file_type = ($media['file_type_number'] == 1) ? "picture" : "video";
            $response[$key]['original_url'] = $client->getMediaCdnThumbUrl($media['bucket'], 'original', $media['file'], $file_type, 0);
            $response[$key]['video_source'] = ($media['file_type_number'] == 1) ? "" : $client->getMediaCdnThumbUrl($media['bucket'], 'source', $media['file'], $file_type, 0);
            $response[$key]['media_type'] = (int) $media['file_type_number'];
            $response[$key]['height'] = (int) $media['file_height'];
            $response[$key]['width'] = (int) $media['file_width'];
        }
        return $response;
    }

    /**
     * Get api post caption
     * @param type $source
     * @param type $attrs
     * @return type
     */
    public function getApiPostCaption($source = 'google', $attrs) {
        $caption = "";
        if ($source == 'google') {
            $caption = !empty($attrs['caption']) ? ucfirst(str_replace("_", " ", $attrs['caption'])) : "";
        } else if ($source == 'itunes' || $source == 'imdb') {
            $caption = isset($attrs['stars']) && !empty($attrs['stars']) ? $attrs['stars'] : (!empty($attrs['owner']) ? $attrs['owner'] : "");
        } else if ($source == 'ibook' || $source == 'youtube') {
            $caption = isset($attrs['owner']) ? $attrs['owner'] : "";
        } else if ($source == 'web') {
            $caption = $attrs['bg_image'];
        }
        return $caption;
    }

    /**
     * Extract api post trailers
     * @param type $attrs
     * @return type
     */
    public function extractApiPostTrailers($attrs = []) {
        $trailers = [];
        if (in_array($attrs['source_type'], ['google', 'imdb']) && !empty($attrs['trailers'])) {
            $trailers = $attrs['trailers'];
        }
        return $trailers;
    }

    /**
     * Extrait trailer URL
     * @param type $attrs
     * @return type
     */
    public function extractApiPostSourceUrl($attrs = []) {
        $url = "";
        if (in_array($attrs['item_type_number'], [config('general.post_item_types.television'), config('general.post_item_types.movie')])) {
            $url = !empty($attrs['trailers']) ? $this->extraceYoutubeId($attrs['trailers'][0]['url']) : "";
        } else if ($attrs['item_type_number'] == config('general.post_item_types.video')) {
            $url = !empty($attrs['external_url']) ? $this->extraceYoutubeId($attrs['external_url']) : "";
        } else if ($attrs['item_type_number'] == config('general.post_item_types.music')) {
            $url = (isset($attrs['trailer_url']) && !empty($attrs['trailer_url'])) ? $attrs['trailer_url'] : "";
        } else if ($attrs['item_type_number'] == config('general.post_item_types.book')) {
            $url = $attrs['source_link'];
        } else if ($attrs['item_type_number'] == config('general.post_item_types.url')) {
            $url = $attrs['source_link'];
        } else if (in_array($attrs['item_type_number'], [config('general.post_item_types.place'), config('general.post_item_types.food')])) {
            $location_type = isset($attrs["caption"]) && !empty($attrs["caption"]) ? strtolower($attrs["caption"]) : "";
            $location_type = "";
            $icon = !empty(config("g_map.icons_mapping.$location_type")) ? config("g_map.icons_mapping.$location_type") : config('g_map.default_icon_name');
            $url = config('g_map.icon_base_url') . "$icon.png";
        } else if ($attrs['item_type_number'] == config("general.post_item_types.game")) {
            if (isset($attrs['trailer_url']) && !empty($attrs['trailer_url'])) {
                $url = $this->extraceYoutubeId($attrs['trailer_url']);
            }
        }
        return $url;
    }

    private function getUrlClient() {
        // This client is temporarily used
        $urlClient = new TempMediaUrl();
        return $urlClient;
    }

    /**
     * Extract youtube watch ID from URL
     * @param type $url
     * @return type
     */
    private function extraceYoutubeId($url = "") {
        $key = "?v=";
        $sourceKey = "";
        $arr = explode($key, $url);
        if (count($arr) > 1) {
            $sourceKey = $arr[1];
        }
        return $sourceKey;
    }

    /**
     * Fetch api post fayvs cou
     * @param type $post
     * @return type
     */
    public function getFayvCount($post) {
        $fayvs = 1;
        if ($post["post_attributes"]['source_type'] == config('general.post_source_types.google')) {
            $fayvs = isset($post["place"]["fayvs"]) ? (int) $post["place"]["fayvs"] : 1;
        } else {
            $fayvs = isset($post["post_attributes"]["fayvs"]) ? (int) $post["post_attributes"]["fayvs"] : 1;
        }
        return $fayvs;
    }

    private function prepareDiscoverMedia($response, $post) {
        if (in_array($post['post_type_id'], config("general.search_posts"))) {
            if (!isset($post['post_attributes']['thumbnail']) || empty($post['post_attributes']['thumbnail'])) {
                $attr = $post['post_attributes'];
                $response['thumbnail'] = $this->getPreviewCdn($attr['source_id'], $attr['source_type'], $attr['item_type_number'], "medium");
                $response['actual'] = $response['thumbnail'];
            } else {
                $response['thumbnail'] = str_replace("/original/", "/medium/", $post['post_attributes']['thumbnail']);
                $response['actual'] = $post['post_attributes']['thumbnail'];
            }
            $response['bg_color'] = isset($post['post_media'][0]['bg_color']) ? $post['post_media'][0]['bg_color'] : $this->getRandomColor();
            $response['media_type'] = 1;
            $response = $this->getAPIThumbDimensions($response, $post);
        } else {
            $file_type = ($post['post_media'][0]['file_type_number'] == 1) ? "picture" : "video";
            $response['thumbnail'] = $this->getMediaCdnThumbUrl("fayvo-sample", 'medium', $post['post_media'][0]['file'], $file_type, 0);

//            $file_type = ($post['post_media'][0]['file_type_number'] == 1) ? "picture" : "video";
//            $response['thumbnail'] = $this->getMediaFilePath($post['post_media'][0]['file'], strtoupper($file_type[0]), 'medium');

            $response['bg_color'] = $post['post_media'][0]['bg_color'];
            $response['media_type'] = (int) $post['post_media'][0]['file_type_number'];
            $response['width'] = (int) $post['post_media'][0]['medium_file_width'];
            $response['height'] = (int) $post['post_media'][0]['medium_file_height'];
            $response['actual'] = $this->getActualUrl($post['post_media'][0]);
        }

        return $response;
    }

    /**
     * Get API Post Dimensions
     * @param type $response
     * @param type $post
     * @return type
     */
    private function getAPIThumbDimensions($response = [], $post) {
        $response['width'] = 0;
        $response['height'] = 0;
        if (isset($post["post_attributes"]["height"]) && $post["post_attributes"]["height"] > 0) {
            $response['width'] = (int) $post["post_attributes"]["width"];
            $response['height'] = (int) $post["post_attributes"]["height"];
        } else if (isset($post["post_media"]) && !empty($post["post_media"])) {
            $response['width'] = (int) $post["post_media"][0]["file_width"];
            $response['height'] = (int) $post["post_media"][0]["file_height"];
        } else {
            list($width, $height) = getimagesize($post["post_attributes"]['thumbnail']);
            $response['width'] = (int) $width;
            $response['height'] = (int) $height;
        }
        return $response;
    }

    private function prepareGoogleMapThumbs($response, $thumb_status = false, $post_object) {
        if (!$thumb_status) {
            $response['thumbnail'] = $this->prepareDiscoverGoogleMapUrl($post_object["place"], $post_object["post_location"], $post_object["id"]);
            $response['width'] = config('g_map.map_dimensions.size_400_600.width');
            $response['height'] = config('g_map.map_dimensions.size_400_600.height');
            $response['bg_color'] = config('g_map.map_default_bg_color');
        }
        return $response;
    }

}
