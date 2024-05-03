<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of FayvoActivity
 *
 * @author rizwan
 */
class FayvoActivity extends Model {

    protected $primarykey = 'id';
    protected $table = 'fayvo_activities';
    protected $fillable = ['user_id', 'related_to', 'relation_id', 'comment_text', 'status', 'domain', 'relation_type', 'item_type_number',
        'media', 'media_type', 'object_id', 'created_at', 'updated_at', 'file_width', 'file_height', "source_type", 'item_type',
        'medium_file_width', 'medium_file_height', 'notification_file_width',
        'thumbnail', 'bg_image', 'notification_file_height', 'bucket', 'bg_color', 'caption'];

    /**
     * Delete activities
     * @param type $post_id
     * @return type
     */
    public static function deleteActivitiesByPost($post_id) {
        return FayvoActivity::where(function($sql) use($post_id) {
                    $sql->whereIn('domain', ['C', 'L', 'M', 'T']);
                    $sql->where('relation_id', '=', $post_id);
                })->delete();
    }

    /**
     * Delete activities by comment
     * @param type $comment_id
     * @return type
     */
    public static function deleteActivitiesByComment($comment_id) {
        return FayvoActivity::where(function($sql) use($comment_id) {
                    $sql->whereIn('domain', ['C', 'M']);
                    $sql->where('object_id', '=', $comment_id);
                })->delete();
    }

}
