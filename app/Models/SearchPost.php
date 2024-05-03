<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchPost extends Model {

    protected $primarykey = 'id';
    protected $table = 'search_posts';
    protected $fillable = ['id', 'user_id', 'post_type_id', 'title', 'rating', "attribute_id",
        'thumbnail', 'bg_image', 'source_type', 'source_link', 'item_type', "postable_type",
        'item_type_number', 'archive', 'created_at', 'updated_at', 'post_type_id'];
    protected $with = ["postMedia", "postable"];

    /**
     * Morph relation
     * @return type
     */
    public function postable() {
        return $this->morphTo(null, null, "attribute_id");
    }

    public function post() {
        return $this->morphToMany('App\Models\UserPost', 'postable', 'id');
    }

    /**
     * Media
     * @return type
     */
    public function postMedia() {
        return $this->hasMany('App\Models\PostMedia', 'user_post_id', 'id');
    }

    /**
     * Get search post attributess
     * @return type
     */
    public function searchAttributes() {
        return $this->hasOne("App\Models\PostSearchAttribute", "source_id", "source_id");
    }

    /**
     * This relation used for top search result
     * @return type
     */
    public function selfSourcePost() {
        return $this->hasOne("App\Models\SearchPost", "source_id", "source_id");
    }

    public function location() {
        return $this->hasOne("App\Models\Location", "fs_location_id", "source_id");
    }

    /**
     * Get top search post
     * @param type $limit
     * @return type
     */
    public static function getTopSearchPosts($limit) {
        $results = SearchPost::select(\DB::raw("COUNT(*) as total,source_id"))->where(function($sql) {
                            $sql->where("source_id", "<>", "");
                        })->with(["selfSourcePost" => function($sql) {
                                $sql->select(["id", "title", "thumbnail", "rating", "bg_image", "item_type", "item_type_number", "source_link", "source_type", "source_id"]);
                            }])
                        ->groupBy("source_id")->havingRaw('total > 0')
                        ->orderBy("total", "DESC")->limit($limit)->get();
        return $results;
    }

}
