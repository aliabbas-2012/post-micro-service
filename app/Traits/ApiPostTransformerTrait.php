<?php

namespace App\Traits;

/**
 * 
 * @author Ali Abbas
 */
trait ApiPostTransformerTrait {

    /**
     * 
     * @param type $post
     *      post object
     * @param type $preview
     *      API Response from Node Server
     * @return type
     */
    public function prepareAPIResponse($post, $preview) {
        $response = [];
        switch ($post->item_type_number) {
            case config("general.post_item_types.television"):
                $response["movie_tv"] = $this->prepareMovieTvResponse($preview, $post);
                break;
            case config("general.post_item_types.movie"):
                $response["movie_tv"] = $this->prepareMovieTvResponse($preview, $post);
                break;
            case config("general.post_item_types.place"):
                $response["place"] = $this->prepareLocationResponse($preview, $post);
                break;
            case config("general.post_item_types.food"):
                $response["place"] = $this->prepareLocationResponse($preview, $post);
                break;
            case config("general.post_item_types.book"):
                $response["book"] = $this->prepareBookResponse($post, $preview);
                break;
            case config("general.post_item_types.video"):
                $response["video"] = $this->prepareVideoResponse($post, $preview);
                break;
            case config("general.post_item_types.music"):
                $response["music"] = $this->prepareMusicResponse($post, $preview);
                break;
            case config("general.post_item_types.url"):
                $response = $this->prepareURLResponse($post);
                break;
            case config("general.post_item_types.game"):
                $response["game"] = $this->prepareGameResponse($post, $preview);
                break;
            default :
                break;
        }
        if (!empty($preview) && isset($preview['model']['related_media'])) {
            $response["related_media"] = !empty($preview['model']['related_media']) ? $preview['model']['related_media'] : [];
        } else {
            $response["related_media"] = $this->prepareRelatedMedia($post, $response);
        }
        return $response;
    }

    /**
     * Prepare Google Post
     * @param type $post
     * @return type
     */
    public function prepareLocationResponse($preview, $api) {
        /*
          $response = [];
          $location = isset($post->fs_location_id) ? $post : $post->postable;
          $map_screen_url = isset($preview["model"]['map_screen']) ? $preview["model"]['map_screen'] : $this->prepareMapScreenPath($location->toArray());
          if (isset($preview["model"]["place"])) {
          $place = $preview["model"]["place"];
          $response = [
          "id" => $place["id"],
          "name" => $place["name"],
          "lat" => !empty($place["lat"]) ? (string) $place["lat"] : "",
          "lon" => !empty($place["lon"]) ? (string) $place["lon"] : "",
          "types" => !empty($place["tags"]) ? (string) $place["tags"] : "",
          "map_screen" => $map_screen_url,
          "category" => "",
          "rating" => !empty($place["rating"]) ? (string) $place["rating"] : "0/10",
          ];
          } else {
          $response = [
          "id" => (string) $location->fs_location_id,
          "name" => (string) $location->location_name,
          "lat" => (string) $location->latitude,
          "lon" => (string) $location->longitude,
          "types" => !empty($location->tags) ? (string) $location->tags : "",
          "map_screen" => $map_screen_url,
          "category" => "",
          "rating" => (isset($location->rating) && !empty($location->rating)) ? (string) $location->rating : "0/10",
          ];
          }
          $response["rating"] = $this->arabicW2e($response["rating"]);
          return $response;
         */

        ///////////////////////////////////////////////////////////
        $response = [];
        $place = (!empty($api) && $api instanceof \App\Models\Location) ? $api->toArray() : (array) $api;
        $map_screen_url = isset($preview["model"]['map_screen']) ? $preview["model"]['map_screen'] : $this->prepareMapScreenPath($place);
        if (isset($preview["model"]["place"])) {
            $place = $preview["model"]["place"];
            $response = [
                "id" => $place["id"],
                "name" => $place["name"],
                "lat" => !empty($place["lat"]) ? (string) $place["lat"] : "",
                "lon" => !empty($place["lon"]) ? (string) $place["lon"] : "",
                "types" => !empty($place["tags"]) ? (string) $place["tags"] : "",
                "map_screen" => $map_screen_url,
                "category" => "",
                "rating" => !empty($place["rating"]) ? $this->arabicW2e($place["rating"]) : "N/A",
            ];
        } else {
            $rating = $this->roundRating($api->rating);
            $response = [
                "id" => (string) $api->fs_location_id,
                "name" => (string) $api->location_name,
                "lat" => (string) $api->latitude,
                "lon" => (string) $api->longitude,
                "types" => !empty($api->tags) ? (string) $api->tags : "",
                "map_screen" => $map_screen_url,
                "category" => "",
                "rating" => $rating > 0 ? $this->arabicW2e("{$rating}/10") : "N/A",
            ];
        }
        return $response;
    }

    /**
     * Prepare Video Youtube
     * @param type $post
     * @param type $preview
     * @return type
     */
    private function prepareVideoResponse($api, $preview) {
//        $db_object = isset($post->thumb_status) ? $post : $post->postable;
//        return [
//            'source_id' => $db_object->source_id,
//            'channel' => (string) $db_object->owner
//        ];


        if (!empty($preview["video"])) {
            return [
                'source_id' => $preview["video"]["source_id"],
                'channel' => (string) $preview["video"]["channel"]
            ];
        } else {
            return [
                'source_id' => $api->source_id,
                'channel' => (string) $api->owner,
            ];
        }
    }

    /**
     * Prepare Book 
     * @param type $post
     * @param type $preview
     * @return type
     */
    private function prepareBookResponse($api, $preview) {
        /*
          $db_object = isset($post->thumb_status) ? $post : $post->postable;
          $rating = $preview["model"]["book"]["rating"];
          $authors = $db_object->owner;
          if (isset($preview["model"]["book"]["author"]) && !empty($preview["model"]["book"]["author"])) {
          $authors = $preview["model"]["book"]["author"];
          } else if (isset($preview["model"]["book"]["authors"]) && !empty($preview["model"]["book"]["authors"])) {
          $authors = $preview["model"]["book"]["authors"];
          }
          return [
          'genres' => !empty($preview["model"]["book"]["genres"]) ? $preview["model"]["book"]["genres"] : $db_object->tags,
          'rating' => $this->arabicW2e($rating),
          'author' => $authors,
          'authors' => $authors,
          ];
         */
        //////////////////////////
        $authors = $api->owner;
        if (!empty($preview)) {
            $rating = $preview["model"]["book"]["rating"];

            if (!empty($preview["model"]["book"]["author"])) {
                $authors = $preview["model"]["book"]["author"];
            } else if (!empty($preview["model"]["book"]["authors"])) {
                $authors = $preview["model"]["book"]["authors"];
            }

            return [
                'genres' => !empty($preview["model"]["book"]["genres"]) ? $preview["model"]["book"]["genres"] : $api->tags,
                'rating' => !empty($rating) ? $this->arabicW2e($rating) : "N/A",
                'author' => $authors,
                'authors' => $authors,
            ];
        } else {
            $rating = $this->roundRating($api->rating);
            return [
                'genres' => $api->tags,
                'rating' => $rating > 0 ? $this->arabicW2e("{$rating}/5") : "N/A",
                'author' => $authors,
                'authors' => $authors,
            ];
        }
    }

    /**
     * Prepare Music
     * @param type $preview
     * @return type
     */
    private function prepareMusicResponse($api, $preview) {
        /*
          $db_object = isset($post->thumb_status) ? $post : $post->postable;
          $singers = $db_object->stars;
          if (isset($preview["model"]["music"]["singer"]) && !empty($preview["model"]["book"]["singer"])) {
          $singers = $preview["model"]["music"]["singer"];
          } else if (isset($preview["model"]["music"]["singers"]) && !empty($preview["model"]["book"]["singers"])) {
          $singers = $preview["model"]["music"]["singers"];
          }
          $data = [
          'play_url' => !empty($preview['model']['music']['play_url']) ? $preview['model']['music']['play_url'] : "",
          'singers' => $singers,
          'singer' => $singers,
          'album' => !empty($preview['model']['music']['album']) ? $preview['model']['music']['album'] : "",
          ];

          if (empty($data)) {
          $data = [
          'play_url' => $db_object->trailer_url,
          'singer' => $singers,
          'singer' => $singers,
          'album' => $db_object->owner,
          ];
          }
          return $data;
         */
        //////////////////
        if (!empty($preview)) {
            $respnse = [
                'play_url' => !empty($preview['model']['music']['play_url']) ? $preview['model']['music']['play_url'] : "",
                'singer' => isset($preview["music"]["singers"]) ? $preview["music"]["singers"] : "",
                'singers' => isset($preview["music"]["singers"]) ? $preview["music"]["singers"] : "",
                'album' => !empty($preview['model']['music']['album']) ? $preview['model']['music']['album'] : "",
            ];
            if (env("APP_ENV") == "staging" && isset($preview['model']["music"]["gener"])) {
                $respnse["singers"] = $respnse["singers"] . " ({$preview['model']["music"]["gener"]})";
            }
            return $respnse;
        } else {

            $respnse = [
                'play_url' => $api->trailer_url,
                'singers' => $api->stars,
                'singer' => $api->stars,
                'album' => $api->owner,
            ];
            if (env("APP_ENV") == "staging" && !empty($api->tags)) {
                $respnse["singers"] = $respnse["singers"] . " ({$api->tags})";
            }
            return $respnse;
        }
    }

    /**
     * 
     * @param type $preview
     * @return type
     */
    private function prepareGameResponse($api, $preview) {

//        return [
//            'trailer_url' => !empty($preview['model']['game']['trailer_url']) ? $preview['model']['game']['trailer_url'] : "",
//            'genres' => !empty($preview['model']['game']['genres']) ? $preview['model']['game']['genres'] : "",
//            'rating' => $this->arabicW2e($preview['model']['game']['rating']),
//            'source_id' => !empty($preview['model']['game']['source_id']) ? $preview['model']['game']['source_id'] : "",
//        ];



        if (!empty($preview['model']['game'])) {
            return [
                'trailer_url' => !empty($preview['model']['game']['trailer_url']) ? $preview['model']['game']['trailer_url'] : "",
                'genres' => !empty($preview['model']['game']['genres']) ? $preview['model']['game']['genres'] : "",
                'rating' => !empty($preview['model']['game']['rating']) ? $this->arabicW2e($preview['model']['game']['rating']) : "N/A",
                'source_id' => !empty($preview['model']['game']['source_id']) ? $preview['model']['game']['source_id'] : "",
            ];
        } else {

            $rating = $this->roundRating($api->rating);
            $youtube_source_id = "";
            if (!empty($api->trailer_url)) {
                $youtube_source_id = explode("?v=", $api->trailer_url)[1];
            } else {
                $game = $api instanceof \App\Models\PostSearchAttribute ? $api->toArray() : (array) $api;
                $youtube_source_id = $this->searchYoutuveTrailerUrl($game);
            }
            return [
                'source_id' => $youtube_source_id,
                'trailer_url' => $api->trailer_url,
                'genres' => $api->tags,
                'rating' => $rating > 0 ? $this->arabicW2e("{$rating}/100") : "N/A",
            ];
        }
    }

    /**
     * 
     * @param type $preview
     * @return type
     */
    private function prepareMovieTvResponse($preview, $api) {

        if (!empty($preview)) {
            $trailer_url = !empty($preview['model']['movie_tv']['trailer_url']) ? $preview['model']['movie_tv']['trailer_url'] : $this->searchYoutuveTrailerUrl($preview['model']['movie_tv']);
            return [
                'trailer_url' => $trailer_url,
                'genres' => !empty($preview['model']['movie_tv']['genres']) ? $preview['model']['movie_tv']['genres'] : "",
                'rating' => !empty($preview['model']['movie_tv']['rating']) ? $this->arabicW2e($preview['model']['movie_tv']['rating']) : "N/A",
                'source_id' => !empty($preview['model']['movie_tv']['source_id']) ? $preview['model']['movie_tv']['source_id'] : "",
            ];
        } else {
            $rating = $this->roundRating($api->rating);
            $youtube_source_id = "";
            if (!empty($api->trailer_url)) {
                $youtube_source_id = explode("?v=", $api->trailer_url)[1];
            } else {
                $youtube_source_id = $this->searchYoutuveTrailerUrl((array) $api);
            }
            return [
                'trailer_url' => !empty($api->trailer_url) ? $api->trailer_url : "",
                'genres' => !empty($api->tags) ? $api->tags : "",
                'rating' => $rating > 0 ? $this->arabicW2e("{$rating}/10") : "N/A",
                'source_id' => $youtube_source_id,
            ];
        }
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    private function prepareURLResponse($post) {
        return [
            'post_description' => !empty($post->description) ? strip_tags($post->description) : "",
            "web" => [
                "original_source_link" => $post->external_url
            ]
        ];
    }

    /**
     * 
     * @param type $api
     * @param type $response
     * @return type
     */
    public function prepareRelatedMedia($api, $response = []) {
        $related_media = [];
        if (($api->item_type_number == 3 || $api->item_type_number == 4) && !empty($api->gallery)) {
            $gallery = json_decode($api->gallery, true);

            foreach ($gallery as $item) {
                $related_media[] = [
                    "title" => "", "play_url" => "", "type" => 1,
                    "width" => isset($item["width"]) ? (int) $item["width"] : 0, "height" => isset($item["height"]) ? (int) $item["height"] : 0,
                    "thumbnail" => config("image.fv-cdn-url") . "cdn/gallery?key={$item["thumbnail"]}"
                ];
            }
        } else if (in_array($api->item_type_number, [6, 8, 10])) {
            $inner_key = ($api->item_type_number == 10) ? "game" : "movie_tv";
            if (!empty($api->trailers)) {
                $photo_and_videos = json_decode($api->trailers, true);
                foreach ($photo_and_videos as $item) {
                    $media_type = (isset($item["url"]) && !empty($item["url"])) ? 2 : 1;
                    $related_item = [
                        "title" => isset($item["title"]) ? $item["title"] : "", "type" => (int) $media_type,
                        "thumbnail" => $item["thumbnail"], "source_id" => "",
                    ];
                    if ($media_type == 2 & $response[$inner_key]["source_id"] != $item["key"]) {
                        $related_item["source_id"] = $item["key"];
                        $related_item["play_url"] = config("g_map.youtube_site_base_url") . $item["key"];
                        $related_item["thumbnail"] = $item["thumbnail"];
                        $related_media[] = $related_item;
                    } else if ($media_type == 1 && !empty($item["thumbnail"])) {
                        $related_item["thumbnail"] = $item["thumbnail"];
                        $related_item["title"] = ""; // @empty title on @shehzad request
                        $related_media[] = $related_item;
                    }
                }
            }
        } else if ($api->item_type_number == 1 && !empty($api->trailers)) {
            $other_songs = json_decode($api->trailers, true);

            foreach ($other_songs as $item) {
                $related_media[] = [
                    "title" => "",
                    "type" => 9,
                    "play_url" => $item["url"],
                    "width" => isset($item["width"]) ? (int) $item["width"] : 0, "height" => isset($item["height"]) ? (int) $item["height"] : 0,
                    "thumbnail" => $item["thumbnail"]
                ];
            }
        }
        return $related_media;
    }

}
