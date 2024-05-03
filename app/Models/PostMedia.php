<?php

/**
 * @author Rizwan Saleem
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;

/**
 * Description of UserPost
 *
 * @author rizwan
 */
class PostMedia extends Model {

    use \App\Traits\MediaBucketPathTrait;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'post_media';
    protected $appends = ['thumb', 'medium', 'original', "home", "source"];

    /**
     * Relashion with user
     * @return type
     */
    public function post() {
        return $this->belongsTo('App\Models\UserPost', 'user_post_id', 'id');
    }

    /**
     * delete locations by post ids
     * @param type $post_ids
     * @return boolean
     */
    public static function deleteMediaByPostIds($post_ids) {
        return static::whereIn('user_post_id', $post_ids)->delete();
    }

    /**
     * Append media urls
     * @return type
     */
    public function isUrlGenerationValid() {
        if (isset($this->attributes['file']) && !empty($this->attributes["bucket"]) && !empty($this->attributes["file_type"])) {
            return true;
        }
        return false;
    }

    /**
     * Get media URL
     * @param type $type
     * @return type
     */
    public function getMediaURl($type = 'thumb') {
        $url = "";
        if ($this->isUrlGenerationValid()) {
            $url = $this->getMediaCdnThumbUrl($this->attributes["bucket"], $type, $this->attributes['file'], $this->attributes["file_type"]);
        }
        return $url;
    }

    /**
     * Append media urls
     * @return type
     */
    public function getThumbAttribute() {
        return $this->getMediaURl('thumb');
    }

    public function getOriginalAttribute() {
        return $this->getMediaURl('original');
    }

    public function getMediumAttribute() {
        return $this->getMediaURl('medium');
    }

    public function getHomeAttribute() {
        return $this->getMediaURl('home');
    }

    public function getSourceAttribute() {
        return $this->getMediaURl('source');
    }

    /**
     * 
     * @param type $post_ids
     * @return type
     */
    public static function getPostMedia($post_ids) {
        $arr = [];
        $redis = Redis::connection('post_media');
        if (!empty($post_ids)) {
            if (false && $mGet = array_filter($redis->hMGet("post_media", $post_ids))) {
                foreach ($mGet as $data) {
                    $medias = json_decode($data, true);
                    foreach ($medias as $media) {
                        $arr[$media["user_post_id"]][] = $media;
                    }
                }
            }
        }

        if ($post_ids = array_diff($post_ids, array_keys($arr))) {

            $medias = PostMedia::whereIn("user_post_id", $post_ids)->orderBy("id", "ASC")->get()->toArray();

            foreach ($medias as $media) {
                $arr[$media["user_post_id"]][] = $media;
            }

            if (!empty($arr)) {
                foreach ($arr as $post_id => $media) {
                    $hmArr[$post_id] = json_encode($media);
                }
            }

            if (!empty($hmArr)) {
                $redis->hmset("post_media", $hmArr);
            }
        }

        return $arr;
    }

}
