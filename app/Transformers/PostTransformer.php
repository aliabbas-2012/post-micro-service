<?php

namespace App\Transformers;

/**
 * Post Transformer
 * @author ali
 */
class PostTransformer extends BaseTransformer {

    /**
     * It will only contains post and its first media URL
     * @param type $post
     * @return type
     */
    public function transform($post) {
        return $this->preparePostResponseWithRelations($post);
    }

    public function prepareSimplePostResponse($post) {

        $resp = [
            "id" => $post->id,
            "created_at" => $post->created_at_app,
                ] + $this->prepareSingleMedia($post->postMedia->first(), $post->post_type_id);
        if ($post->post_type_id == config('general.post_type_index.search')) {

            $resp["search"] = [
                "title" => $post->postable->title,
                "source_type" => $post->postable->source_type,
                "item_type_number" => $post->postable->item_type_number,
                "item_type" => $post->postable->item_type,
                "thumbnail" => $post->postable->thumbnail,
                "bg_image" => $post->postable->bg_image,
            ];
        }
        return $resp;
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    public function preparePostResponseWithRelations($post) {
        $data = $this->preparePostResponse($post);

        if ($post->post_type_id == config('general.post_type_index.search')) {
            $data['search_post'] = $this->prepareSearchPostArr($post->post->postable);
            $data["fayvs"] = 0;
            $data["fayvs_profiles"] = [];
        }
        $data["media"] = $this->prepareMedia($post->post->postable);
        if (!empty($post->post->place) && $post->post->place != NULL) {
            $data += $this->preparePlace($post->post);
        } else if ($post->post_type_id == config("general.post_type_index.search") && $post->post->postable->source_type == config("general.post_source_types.google") && !empty($post->post->postable->postable)) {
            // instant fix
            $post->post->place = $post->post->postable->postable;
            $data += $this->preparePlace($post->post);
        }
        $data["boxes"] = $this->prepareBoxes($post->post);
        return $data;
    }

    /**
     * 
     * @param type $post
     * @return type
     */
    public function preparePostResponse($post) {
        $data = [
            "id" => $post->post->id,
            "content" => !empty($post->post->text_content) ? $post->post->text_content : "",
            "type" => $post->post_type_id,
            "web_url" => !empty($post->post->web_url) ? $post->post->web_url : "",
            "local_db_path" => $post->post->local_db_path,
            "likes" => !empty($post->postTotalLikes) ? $post->postTotalLikes->total_likes : 0,
            "comments" => !empty($post->postTotalComments) ? $post->postTotalComments->total_comments : 0,
            "tags" => !empty($post->postTotalTags) ? $post->postTotalTags->total_tags : 0,
            "is_liked" => !empty($post->postLikesByUser) ? true : false,
            "user" => $this->prepareUser($post->post),
            "created_at" => $post->post->created_at_app,
            "short_code" => !empty($post->post->short_code) ? $post->post->short_code : "",
        ];
        if (in_array($post->post_type_id, [config("general.multi_media_types.multi_media")]) && $collage = $this->generateCollage($post->post)) {
            $data["collage"] = $collage;
        }
        return $this->postSearchAttributes($post, $data);
    }

    /**
     * Prepare post search attributes array
     * @param type $post
     * @param type $data
     * @return type
     */
    public function postSearchAttributes($post, $data) {
//        if (isset($post->postDetail) && $post->postDetail) {
        if (!isset($post->postDetail) || $post->postDetail) {
            if (!in_array($post->post->post_type_id, config("general.media_type_posts"))) {
                $obj = new SearchAttributesTransformer();
                $data["search_attributes"] = ($post->post->postable->source_type != 'web' && !empty($post->post->postable->postable)) ? $obj->transform($post->post->postable->postable->toArray()) : (new \stdClass());
            }
        }
        return $data;
    }

    public function prepareMedia($post) {
        $medias = [];
        foreach ($post->postMedia as $post_media) {
            array_push($medias, $this->extractWidthHeightImages($post_media));
        }
        return $medias;
    }

    public function generateCollage($post) {
        return [
            "path" => $this->getCollageUrl($post->postable->postMedia[0]->bucket) . $post->local_db_path . ".jpg",
            "width" => $post->postable->postMedia[0]->collage_file_width,
            "height" => $post->postable->postMedia[0]->collage_file_height,
        ];
        return false;
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
            "picture" => $this->getUserPictureThumb($post->user),
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
        $boxes = [];
        foreach ($post->postBoxesPivot as $box) {
            array_push($boxes, ["id" => $box->box_id, "name" => $box->name, "status" => $box->status]);
        }
        return $boxes;
    }

    public function extractWidthHeightImages($post_media) {
        $media = array();
        $media['type'] = $post_media->file_type_number;
        $media['media_type'] = $post_media->file_type_number;
        $media['post_id'] = $post_media->user_post_id;
        $media['bucket'] = $post_media->bucket;
        $media['cdn'] = $this->getPostCdnUrl($post_media->bucket, $post_media->user_post_id);

        switch ($post_media->file_type_number) {
            case 1:
                $media['actual'] = $post_media->original;
                $media['thumbnail'] = $post_media->home;
//                $media['actual'] = $this->getPostCdnUrl($post_media->bucket) . config('image.' . $post_media->bucket . '_post_images_original') . $post_media->file;
//                $media['thumbnail'] = $this->getPostCdnUrl($post_media->bucket) . config('image.' . $post_media->bucket . '_post_images_home') . $post_media->file;
                break;
            case 2:
                $media['thumbnail'] = $post_media->home;
                $media['actual'] = $post_media->source;
//                $media['actual'] = $post_media->original;
//                $media['thumbnail'] = $this->getPostCdnUrl($post_media->bucket) . config('image.' . $post_media->bucket . '_post_videos_home') . $post_media->file_base_name . ".jpg";

                break;
            default:
                "";
        }
        $media['width'] = !empty($post_media->medium_file_width) ? (int) $post_media->medium_file_width : (int) $post_media->file_width;
        $media['height'] = !empty($post_media->medium_file_height) ? (int) $post_media->medium_file_height : (int) $post_media->file_height;
        $media['file_name'] = $post_media->file;
        $media['bg_color'] = !empty($post_media->bg_color) ? $post_media->bg_color : config('general.default_bg_color');

        return $media;
    }

    public function prepareSingleMedia($post_media, $post_type = 0) {
        $media = array();
//        switch ($post_media->file_type_number) {
//            case 1:
//                $media['thumbnail'] = $this->getPostCdnUrl($post_media->bucket) . config('image.post_img_medium_url') . $post_media->file;
//                break;
//            case 2:
//                $media['thumbnail'] = $this->getPostCdnUrl($post_media->bucket) . config('image.post_video_thumbnail_url') . $post_media->file_base_name . ".jpg";
//                break;
//            default:
//                "";
//        }
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

    public function getDumyMediaArr($post_id) {
        return [
            [
                "type" => 1,
                "media_type" => 1,
                "post_id" => $post_id,
                "bucket" => "fayvo-sample",
                "cdn" => "http://d1lkqsdr30qepu.cloudfront.net/staging/posts/",
                "path" => "image.fayvo-sample_post_videos_home",
                "actual" => "http://d1lkqsdr30qepu.cloudfront.net/staging/posts/images/original/compress_749uyiwl14932043481493204348.jpg",
                "thumbnail" => "http://d1lkqsdr30qepu.cloudfront.net/staging/posts/images/home/compress_749uyiwl14932043481493204348.jpg",
                "width" => 720,
                "height" => 540,
                "file_name" => "compress_749uyiwl14932043481493204348.jpg",
                "bg_color" => "#C9273E"
            ],
            [
                "type" => 2,
                "media_type" => 2,
                "post_id" => $post_id,
                "bucket" => "fayvo-sample",
                "cdn" => "http://d1lkqsdr30qepu.cloudfront.net/staging/posts/",
                "path" => "image.fayvo-sample_post_videos_home",
                "actual" => "http://d1lkqsdr30qepu.cloudfront.net/staging/posts/videos/source/e0kuvldtvideo-20181226-114326-59521898723427649821545806942.mp4",
                "thumbnail" => "http://d1lkqsdr30qepu.cloudfront.net/staging/posts/videos/home/e0kuvldtvideo-20181226-114326-59521898723427649821545806942.jpg",
                "width" => 408,
                "height" => 725,
                "file_name" => "e0kuvldtvideo-20181226-114326-59521898723427649821545806942.mp4",
                "bg_color" => "#061220"
            ]
        ];
    }

}
