<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrlPost extends Model {

    protected $primarykey = 'id';
    protected $table = 'url_posts';
    protected $fillable = ['id', 'user_id', 'post_type_id', 'title',
        'thumbnail', 'bg_image', 'archive', 'created_at', 'updated_at'];

    public function post() {
        return $this->morphToMany('App\Models\UrlPost', 'postable', 'id');
    }

    /**
     * This relation used for top search result
     * @return type
     */
    public function selfSourcePost() {
        return $this->hasOne("App\Models\UrlPost", "id", "id");
    }

    /**
     * Create new text post
     * @param type $inputs
     * @return \App\Morphic\TextPost
     */
    public static function createSearchPost($inputs) {
        $post = new SearchPost($inputs);
        if ($post->save()) {
            return $post;
        }
        return [];
    }

    /**
     * Get top URL posts
     * @param type $limit
     * @return type
     */
    public static function getTopUrlPosts($limit = 10) {
        $result = UrlPost::select(\DB::raw("COUNT(*) as total,title,id"))->where(function($sql) {
                            $sql->where("title", "<>", "");
                        })->with(["selfSourcePost" => function($sql) {
                                $sql->select(\DB::raw("id,title,short_description,post_url,thumbnail,bg_image,'' as url_post"));
                            }])
                        ->groupBy("title")->havingRaw('total > 0')
                        ->orderBy("total", "DESC")->limit($limit)->get();
        return $result;
    }

}
