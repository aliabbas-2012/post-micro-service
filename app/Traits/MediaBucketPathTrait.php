<?php

namespace App\Traits;

/**
 * Description of BucketPathTrait
 *
 * @author ali
 */
trait MediaBucketPathTrait {

    public function getCdnUrl($bucket) {

        return config("image.$bucket-cdn-url") . config("image.ENV_FOLDER");
    }

    public function getPostCdnUrl($bucket) {
        return $this->getCdnUrl($bucket) . config("image.POST_FOLDER");
    }

    /**
     * Get post media url with directory
     * @param type $bucket
     * @return type
     */
    public function getMediaCdnUrl($bucket) {

        return $this->getPostCdnUrl($bucket);
    }

    public function getMediaCdnThumbUrl($bucket = "fayvo-sample", $type, $file, $file_type, $post_type = 0) {

        $file_type = config("image.alias_" . $file_type);
        $url = $this->getMediaCdnUrl($bucket) . $this->getMediaPath($bucket, $type, $file_type, $file, $post_type);

        return $url;
    }

    /*
     * @param type $bucket
     * @param type $type
     */

    public function getMediaPath($bucket, $type, $file_type, $file, $post_type) {

        return $this->getSampleBucketPath($bucket, $type, $file_type, $file, $post_type);
    }

    public function getSampleBucketPath($bucket, $type, $file_type, $file, $post_type) {
        if ($file_type == "videos" && $type != "source") {
            $file = str_replace('.mp4', '.jpg', $file);
        }

        $url = config("image.post_" . $file_type . "_" . $type) . $file;

        return $url;
    }

    public function getLiveBucketPath($bucket, $type, $file_type, $file) {
        $url = "";
        switch ($file_type) {
            case 'video':
                $url = config("image.post_video_thumbnail_url") . str_replace('.mp4', '.jpg', $file);
                break;
            default:
                $url = $this->getMediaPathByType($bucket, $type, $file);
                break;
        }
        return $url;
    }

    public function getNewLiveBucketPath($bucket, $type, $file_type, $file) {
        $url = "";
        switch ($file_type) {
            case 'video':
                $url = $this->getVideoMediaPath($type, $file_type);
                break;
            default:
                $url = $this->getMediaPathByType($bucket, $type, $file);
                break;
        }
        return $url;
    }

    public function getMediaPathByType($bucket, $type, $file) {
        $url = "";
        switch ($type) {
            case 'thumb':
                $url = config("image.post_images_thumb_" . $bucket) . $file;
                break;
            case 'medium':
                $url = config("image.post_images_medium_" . $bucket) . $file;
                break;
            case 'original':
                $url = config("image.post_images_original_" . $bucket) . $file;
                break;
            case 'home':
                $url = config("image.post_images_home_" . $bucket) . $file;
                break;
            default:
                break;
        }
        return $url;
    }

    /**
     * Get only collage image url
     * @param type $file
     * @return string
     */
    public function getCollageUrl($file) {
        $url = $this->getPostCdnUrl(config("image.POST_BUCKET")) . config("image.post_collage") . $file;
        return $url;
    }

    /**
     * Get video media path
     * @param type $type
     * @param type $file
     * @return string
     */
    public function getVideoMediaPath($type, $file) {
        $url = "";
        switch ($type) {
            case "source":
                $url = config("image.post_videos_source") . $file;
                break;
            default:
                $url = config("image.post_videos_thumb") . str_replace('.mp4', '.jpg', $file);
                break;
        }
        return $url;
    }

}
