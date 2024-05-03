<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Traits;

/**
 * Description of PostMediaTrait
 *
 * @author rizwan
 */
trait PostMediaTrait {

    /**
     * Get cloud front URL
     * @param type $is_live
     * @return type
     */
    public function getCdnUrl($is_live = false) {
        return config("image.s3_fayvo-sample");
    }

    /**
     * Get post media directory
     * @param type $is_live
     * @return type
     */
    public function getPostMediaDir($is_live = false) {
        $dir = config("image.post_media_dir_staging");
        if ($is_live) {
            $dir = config("image.post_media_dir_live");
        }
        return $dir;
    }

    /**
     * Get post media URL
     * @param type $type
     * @param type $is_live
     * @return type
     */
    public function getPostMediaUrl($type = "text", $is_live = false) {
        $url = $this->getCdnUrl($is_live) . $this->getPostMediaDir($is_live);
        return $url . $type . '/';
    }

    /**
     * Get post media URL
     * @param type $post_type
     * @param type $file
     * @return string
     */
    public function getPostMedia($post_type, $file = null) {
        $url = $this->getPostMediaUrl($post_type, true) . $file;
        return $url;
    }

}
