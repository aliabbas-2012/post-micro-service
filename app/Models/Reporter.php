<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporter extends Model {

    public $timestamps = false;
    protected $table = 'reporters';

    /**
     * Delete repotred comment by COMMENT ID#
     * @param type $post_id
     * @return type
     */
    public static function deleteReportedComment($comment_id = 0) {
        return Reporter::where(function($sql) use($comment_id) {
                    $sql->where("report_type", '=', 'comment');
                    $sql->where("reported_id", '=', $comment_id);
                })->delete();
    }

}
