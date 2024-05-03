<?php

namespace App\Transformers;

/**
 * BoxGroupTransformer
 * @author Akku
 */
class SearchAttributesTransformer extends BaseTransformer {

    public function transform($data) {
        $respones = [];
        if (!empty($data)) {
            if (isset($data['fs_location_id'])) {
                $respones = $this->prepareLocationAttributes($data);
            } else {
                $respones['source_type'] = $data['source_type'];
                $respones['source_id'] = $data['source_id'];
                $respones['owner'] = !empty($data['owner']) ? $data['owner'] : "";
                $respones['title'] = !empty($data['title']) ? $data['title'] : "";
                $respones['caption'] = !empty($data['caption']) ? $data['caption'] : "";
                $respones['description'] = !empty($data['description']) ? $data['description'] : "";
                $respones['rating'] = (string) $data['rating'];
                $respones['total_reviews'] = (string) $data['total_reviews'];
                $respones['total_likes'] = (int) $data['total_likes'];
                $respones['total_dislikes'] = (int) $data['total_dislikes'];
                $respones['total_pages'] = (int) $data['total_pages'];
                $respones['stars'] = !empty($data['stars']) ? $data['stars'] : "";
                $respones['trailers'] = $this->parseMovieTrailers($data["trailers"]);
                $respones['external_url'] = !empty($data["external_url"]) ? $data["external_url"] : "";
                $respones['cover_image'] = !empty($data["cover_image"]) ? $data["cover_image"] : "";
                $respones['height'] = (int) $data["height"];
                $respones['width'] = (int) $data["width"];
                $respones['preview_url'] = !empty($data["trailer_url"]) ? $data["trailer_url"] : "";
                $respones['other_info'] = ($respones['source_type'] == 'imdb') ? (!empty($data["tags"]) ? $data["tags"] : "") : ((!empty($data["release_date"]) && $data["release_date"] != "0000-00-00") ? date("F d, Y", strtotime($data["release_date"])) : "");
            }
        }
        return $respones;
    }

    private function parseMovieTrailers($trailers = "") {
//        return empty($trailers) ? [] : json_decode($trailers);
        // temporary hot fix
        if (empty($trailers)) {
            return [];
        }
        $trailers = json_decode($trailers, true);
        foreach ($trailers as $key => $traler) {
            if (isset($traler["key"]) && !empty($traler["key"])) {
                $trailers[$key]["key"] = (string) $traler["key"];
                if ($traler['source'] == 'itunes') {
                    $trailers[$key]["thumbnail"] = str_replace("30x30", "300x300", $traler["thumbnail"]);
                }
            }
        }
        return $trailers;
    }

    /**
     * Prepare location search attributes
     * @param type $data
     * @return type
     */
    private function prepareLocationAttributes($data) {

        $respones['source_type'] = 'place';
        $respones['source_id'] = (string) $data['fs_location_id'];
        $respones['owner'] = "";
        $respones['caption'] = "";
        $respones['description'] = "";
        $respones['stars'] = "";
        $respones['total_reviews'] = "";
        $respones['total_likes'] = 0;
        $respones['total_dislikes'] = 0;
        $respones['total_pages'] = 0;
        $respones['title'] = (string) $data['location_name'];
        $respones['address'] = !empty($data['address']) ? (string) $data['address'] : "";
        $respones['phone'] = (string) $data['phone'];
        $respones['other_info'] = !empty($data['other_info']) ? (string) $data['other_info'] : "";
        $respones['location_type'] = (string) $data['location_type'];
        $respones['rating'] = !empty($data['rating']) ? (string) $data['rating'] : "0.0";
        $respones['trailers'] = [];
        $respones['external_url'] = "";
        $respones['cover_image'] = "";
        $respones['height'] = 0;
        $respones['width'] = 0;
        $respones['preview_url'] = "";
        $respones['map_screen'] = $this->prepareMapScreenPath($data);
        $respones['trailers'] = $this->parseMovieTrailers($data["gallery"]);
        return $respones;
    }

}
