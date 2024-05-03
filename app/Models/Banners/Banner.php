<?php

namespace App\Models\Banners;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * Description of Banner
 *
 * @author rizwan
 */
class Banner extends Model {

    protected $primaryKey = 'id';
    protected $table = 'banners';
    protected $connection = "ads_pgsql";

    /**
     * Check banner posts
     * @param type $post_ids
     * @return type
     */
    public static function getPostsBanners($post_ids = []) {
        $hmArray = $response = [];
        Redis::select(config("database.redis.user_data.database"));
        $super_key = "banner_posts";
        if ($rows = array_filter(Redis::hmget($super_key, $post_ids))) {
            foreach ($rows as $key => $row) {
                $row = json_decode($row, true);
                $response[$row['module_id']] = $row;
            }
            $post_ids = array_diff(array_keys($response), $post_ids);
        }
        if (!empty($post_ids)) {
            $banners = Banner::select(\DB::raw("id,module_id,module_type"))->whereIn("module_id", $post_ids)
                            ->where('module_type', 'pt')->where('is_archive', '=', 0)->get()->toArray();
            if (!empty($banners)) {
                foreach ($banners as $key => $banner) {
                    $hmArray[$banner['module_id']] = json_encode($banner);
                    $response[$banner['module_id']] = $banner;
                }
                if (!empty($hmArray)) {
                    Redis::hmset($super_key, $hmArray);
                    Redis::expire($super_key, 172800); //2 Days
                }
            }
        }
        return $response;
    }

    /**
     * Check banner boxes
     * @param type $box_ids
     * @return type
     */
    public static function getBoxesBanners($box_ids = []) {
        $hmArray = $response = [];
        Redis::select(config("database.redis.user_data.database"));
        $super_key = "banner_boxes";
        if ($rows = array_filter(Redis::hmget($super_key, $box_ids))) {
            foreach ($rows as $key => $row) {
                $row = json_decode($row, true);
                $response[$row['module_id']] = $row;
            }
            $box_ids = array_diff(array_keys($response), $box_ids);
        }
        if (!empty($box_ids)) {
            $banners = Banner::select(\DB::raw("id,module_id,module_type"))->whereIn("module_id", $box_ids)
                            ->where('module_type', 'bx')->where('is_archive', '=', 0)->get()->toArray();
            if (!empty($banners)) {
                foreach ($banners as $key => $banner) {
                    $hmArray[$banner['module_id']] = json_encode($banner);
                    $response[$banner['module_id']] = $banner;
                }
                if (!empty($hmArray)) {
                    Redis::hmset($super_key, $hmArray);
                    Redis::expire($super_key, 172800); //2 Days
                }
            }
        }
        return $response;
    }

}
