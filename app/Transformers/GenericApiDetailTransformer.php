<?php

namespace App\Transformers;

/**
 * Post Transformer
 * @author ali
 */
class GenericApiDetailTransformer extends BaseTransformer {

    use \App\Traits\ApiPostTransformerTrait;

    /**
     * It will only contains post and its first media URL
     * @param type $arr
     * @return type
     */
    public function transform($model_arr) {
        $model = $model_arr[0];
        $db_object = $model_arr[1];
        $data = $this->prepareAPICommonResponse($model, $db_object);
        $data['comments'] = [];
        $data['likes'] = [];
        return $data;
    }

    /**
     * Prepare source Short Url
     * @param type $post
     * @param type $user
     * @param type $ip
     * @return type
     */
    public function prepareAPICommonResponse($model, $db_object) {
        $data = [
            "id" => $db_object->id,
            "local_db_path" => "{$db_object->source_key}",
            "content" => !empty($model['post_description']) ? trim($model['post_description']) : "",
            "is_liked" => false,
            "reaction_id" => 0,
            "like_count" => 0,
            "comment_count" => 0,
            "tag_count" => 0,
            "created_at" => $this->parseCarbonFormat($db_object->created_at),
            "short_url" => $this->getShortUrl(["id" => $db_object->id, "source_key" => $db_object->source_key], config("general.item_type_by_number.{$db_object->item_type_number}"), "A"),
            "short_code" => (string) base64_encode($db_object->source_key),
            "is_bookmarked" => false,
            "post_type" => 7,
            "type" => 7, // Only user for is_fayved checking
            "entity_type" => "A",
            "categories" => [],
            "view_count" => 0
        ];
        return $this->postApiAttributes($data, $model, $db_object);
    }

    /**
     * Prepare post search attributes array
     * @param type $post
     * @param type $data
     * @param type $user
     * @param type $ip
     * @return type
     */
    public function postApiAttributes($data, $model, $db_object) {

        $thumb_info = $this->getApiPostThumbInfo($model['source_type'], $db_object->toArray(), $model);
        $data["api_post"] = [
            "title" => (string) $model['title'],
            "thumb" => $thumb_info['thumb'],
            "bg_color" => $thumb_info['bg_color'],
            "is_blur" => !boolval($thumb_info['thumb_status']),
            "item_type" => trans("messages.itemType" . ucfirst(strtolower($model['item_type']))),
            "item_type_number" => (int) $model['item_type_number'],
            "source_type" => "{$model['source_type']}",
            "source_id" => "{$model['source_id']}",
            "item_icon" => $model['item_icon'],
            "source_icon" => $model['source_icon'],
            "scheme_url" => $this->getSchemeUrl($model, $db_object),
            "is_fayved" => false,
            "fayvs" => (int) $model['fayvs'],
            "source_url" => !empty($model['source_url']) ? "{$model['source_url']}" : "",
            "post_description" => !empty($model['post_description']) ? strip_tags($model['post_description']) : "",
        ];
        $data["api_post"] += $this->prepareAPIResponse($db_object, ['model' => $model]);
        return $data;
    }

    private function getApiPostThumbInfo($source_type = "imdb", $attr = [], $model = []) {
        $thumb = [];
        $thumb_status = boolval($attr["thumb_status"]);
        if ($source_type == "google" && !$thumb_status) {

            $thumb = [
                "thumb_status" => false,
                "thumb" => $this->prepareMapScreenPath($attr),
                "bg_color" => config('g_map.map_default_bg_color'),
            ];
        } else {
            //sending default bg color
            $thumb = [
                "thumb_status" => $thumb_status,
                "thumb" => $this->getValidThumb($attr["thumbnail"], $model['thumb'], $model),
                "bg_color" => $this->getRandomColor(),
            ];
        }
        return $thumb;
    }

    private function getValidThumb($thumbnail = null, $preview_thumb = null, $model = []) {
        try {
            if (!empty($thumbnail)) {
                list($width, $height) = getimagesize($thumbnail);
                return $thumbnail;
            } else if (!empty($preview_thumb)) {
                return $preview_thumb;
            } else if (!empty($model)) {
                return $this->getPreviewCdn($model['source_id'], $model['source_type'], $model['item_type_number']);
            }
        } catch (\Exception $ex) {
            return !empty($preview_thumb) ? $preview_thumb : $this->getPreviewCdn($model['source_id'], $model['source_type'], $model['item_type_number']);
        }
    }

    /**
     * Prepare scheme URL
     * @param type $model
     * @param type $db_object
     * @return string
     */
    private function getSchemeUrl($model, $db_object) {
        $source_link = $scheme_url = "";
        $source_type = $model['source_type'];
        if ($model['source_type'] == "imdb") {
            $imdb_id = $db_object->source_id;
            if (!empty($db_object->session_id)) {
                $imdb_id = $db_object->session_id;
                $source_link = str_replace(array("http://", "https://"), "", "{$imdb_id}");
            }
        } else if ($model['source_type'] == "youtube") {
            $youtube_source = config('g_map.youtube_site_base_url') . $db_object->source_id;
            $source_link = strip_tags(str_replace(array("http://", "https://"), "", $youtube_source));
        } else if (isset($db_object->external_url) && !empty($db_object->external_url)) {
            $source_link = strip_tags(str_replace(array("http://", "https://"), "", $db_object->external_url));
        }
        if (!empty($source_link)) {
            $device = $model['device_type'];
            $scheme_url = config('g_map.scheme_url.' . $device . ".{$model['source_type']}") . $source_link;
        }

        return $scheme_url;
    }

}
