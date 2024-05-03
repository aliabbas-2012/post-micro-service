<?php

namespace App\Traits;

/**
 * Description of BucketPathTrait
 *
 * @author ali
 */
trait BucketPathTrait {

    public function getCdnUrl($bucket = "fayvo-sample") {

        return config("image.$bucket-cdn-url") . config("image.ENV_FOLDER");
    }

    public function getPostCdnUrl($bucket) {
        return $this->getCdnUrl($bucket) . config("image.POST_FOLDER");
    }

    public function getUserCdnUrl($bucket) {
        return $this->getCdnUrl($bucket) . config("image.USER_FOLDER");
    }

    public function getUserCdnThumbUrl($bucket, $type) {
        return $this->getUserCdnUrl($bucket) . $this->getUserMediaPath($type);
    }

    /**
     * 
     * @param type $bucket
     * @param type $type
     */
    public function getUserMediaPath($type) {
        return config("image.profile_" . $type);
    }

    /**
     * Get collage 
     * @param type $bucket
     * @return type
     */
    public function getCollageUrl($bucket = "fayvo-sample") {
        $url = $this->getPostCdnUrl($bucket) . config("image.collage");
        return $url;
    }

    /**
     * 
     * @param type $bucket
     *      S3 bucket
     * @param type $type
     *      thumb type (medium)
     * @param type $file
     *      file_name
     *          abc.jpg
     * @param type $file_type
     *          picture or video
     * @param type $post_type
     *      NOT IN USE
     * @return string
     */
    public function getMediaCdnThumbUrl($bucket = "fayvo-sample", $type, $file, $file_type, $post_type = 0) {

        $file_type = config("image.alias_" . $file_type);

        $url = $this->getPostCdnUrl($bucket) . $this->getMediaPath($bucket, $type, $file_type, $file, $post_type);

        return $url;
    }

    /**
     * 
     * @param type $bucket
     *      S3 bucket
     * @param type $type
     *      thumb type (medium)
     * @param type $file_type
     *      images or videos
     * @param type $file
     *      file_name
     *          abc.jpg
     * @param type $post_type
     *      NOT IN USE
     * @return type
     */
    public function getMediaPath($bucket, $type, $file_type, $file, $post_type) {

        return $this->getSampleBucketPath($bucket, $type, $file_type, $file, $post_type);
    }

    /**
     * 
     * @param type $bucket
     *      S3 bucket
     * @param type $type
     *      thumb type (medium)
     * @param type $file_type
     *      images or videos
     * @param type $file
     *      file_name
     *          abc.jpg
     * @param type $post_type
     *      NOT IN USE
     * @return type
     */
    public function getSampleBucketPath($bucket, $type, $file_type, $file, $post_type) {
        if ($file_type == "videos" && $type != "source") {
            $file = str_replace('.mp4', '.jpg', $file);
        }

        $url = config("image.post_" . $file_type . "_" . $type) . $file;

        return $url;
    }

    /**
     * Get media play URL only
     * @param type $file
     * @return string
     */
    public function getMediaPlayUrl($file) {
        $cdn = $this->getPostCdnUrl("fayvo-sample");
        $play_url = "{$cdn}" . config("image.post_videos_source") . "{$file}";
        return $play_url;
    }

    /**
     * Get media original URL only
     * @param type $file
     * @return string
     */
    public function getMediaOriginalUrl($file) {
        $cdn = $this->getPostCdnUrl("fayvo-sample");
        $play_url = "{$cdn}" . config("image.post_images_original") . "{$file}";
        return $play_url;
    }

}
