<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class PostSearchAttribute extends Model {

    protected $primarykey = 'id';
    protected $table = 'post_search_attributes';
    protected $fillable = ['id', 'source_id', 'session_id', 'source_type', 'item_type_number', 'is_synced', 'is_available', 'updated_at', 'created_at', 'last_updated_at', 'archive',
        'category_id', 'runtime', 'release_date', 'stars', 'external_url', 'tags', 'is_processed', 'payload', 'other_info', 'trailers', 'thumbnail', 'thumb_status',
        'cover_image', 'trailer_url', 'width', 'height', 'companies', 'price', 'total_pages', 'total_dislikes', 'total_likes', 'total_reviews', 'rating',
        'description', 'caption', 'original_name', 'title', 'director', 'owner', 'fayvs', 'source_key'];

    public function searchpost() {
//        return $this->morphToMany('App\Models\SearchPost', 'postable', 'id');
        return $this->morphMany('App\Models\SearchPost', 'postable', 'postable_type', 'attribute_id');
    }

    public function post() {
        return $this->morphToMany('App\Models\UserPost', 'postable', 'id');
    }

    /**
     * Get attributes by sourceType & sourceID
     * @param type $source_type
     * @param type $source_id
     * @return type
     */
    public static function getSourceBySourceIDType($source_type, $source_id) {
        $response = [];

        if (in_array($source_type, ["place", "google", "food", "location"])) {

            $location = Location::where("fs_location_id", "=", $source_id)->first();
            $response = !empty($location) ? $location->toArray() : [];
        } else {
            $source = PostSearchAttribute::where(function ($sql) use ($source_type, $source_id) {
                        $sql->where("source_type", "=", strtolower($source_type));
                        $sql->where("source_id", "=", $source_id);
                    })->first();
            $response = !empty($source) ? $source->toArray() : [];
        }
        return !empty($response) ? $response : self::getRandomPostAttributes();
    }

    public static function getRandomPostAttributes() {
        return [];
        $source = PostSearchAttribute::where(function ($sql) {
                    
                })->inRandomOrder()->first();

        return !empty($source) ? $source->toArray() : [];
    }

    /**
     * Get post-search-attribute by multiple column type
     * @param type $col
     * @param type $val
     * @return type
     */
    public static function getAPIByCol($col = 'id', $val = '0') {
        return PostSearchAttribute::where($col, '=', $val)->first();
    }

    /**
     * Fetch attributes for cached
     * @param type $source_keys
     * @return type
     */
    public static function getAttrsForCached($source_keys = []) {
        $hmArray = $response = [];
        $redis = Redis::connection();
        $super_key = config("general.redis_keys.api");
        if ($rows = array_filter($redis->hmget($super_key, $source_keys))) {
            foreach ($rows as $key => $row) {
                $row = json_decode($row, true);
                $response[$row['source_key']] = $row;
            }
            $source_keys = array_diff(array_keys($response), $source_keys);
        }
        if (!empty($source_keys)) {
            $columns = ["id", "source_key", "fayvs", "owner", "title",
                "caption", "description", "rating",
                "height", "width", "trailer_url", "thumb_status", "thumbnail",
                "bg_color", "trailers", "is_processed", "tags", "cover_image",
                "external_url", "stars", "release_date",
                "created_at", "updated_at", "source_id",
                "session_id", "source_type", "item_type_number", "is_available",
                \DB::raw("0 as fayvs,'A' as content_type")];
            $query = PostSearchAttribute::select($columns)->whereIn("source_key", $source_keys);
            if ($attrs = $query->get()->toArray()) {
                foreach ($attrs as $key => $attr) {
                    $hmArray[$attr['source_key']] = json_encode($attr);
                    $response[$attr['source_key']] = $attr;
                }
                $redis->hmset($super_key, $hmArray);
                $redis->expire($super_key, 172800); //2 Days
            }
        }
        return $response;
    }

   
}
