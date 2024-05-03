<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TextPost extends Model {

    use \App\Traits\PostMediaTrait;

    protected $primarykey = 'id';
    protected $table = 'text_posts';
    protected $fillable = ['id', 'user_id', 'bg_color', 'bg_opacity', 'bg_image', 'content',
        'font_color', 'font_size', 'font_family', 'is_bold', 'is_italic', 'file', 'size',
        'file_widht', 'file_heiht', 'archive', 'created_at', 'updated_at', 'post_type_id'];
    protected $appends = ["file", "bg_image"];

    public function post() {
        return $this->morphToMany('App\Models\UserPost', 'postable', 'id');
    }

    /**
     * Get file attribute URL
     * @return type
     */
    public function getFileAttribute() {
        $url = "";
        if (!empty($this->attributes["file"])) {
            $url = $this->getPostMedia("text", $this->attributes["file"]);
        }
        return $url;
    }

    /**
     * Get background image URL
     * @return type
     */
    public function getBgImageAttribute() {
        $url = "";
        if (!empty($this->attributes["bg_image"])) {
            $url = $this->getPostMedia("text", $this->attributes["bg_image"]);
        }
        return $url;
    }

}
