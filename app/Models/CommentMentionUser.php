<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentMentionUser extends Model {

    protected $table = 'comment_mention_users';
    protected $primaryKey = 'id';
    protected $guarded = array();
    public $timestamps = false;
    public $fillable = array('user_posts_id', 'post_comments_id', 'user_id', 'archive','post_owner','comment_owner');

    public function user() {
        return $this->hasOne('App\Models\User', 'id', 'user_id')->select(array('id', 'uid', 'username'));
    }

    /**
     * each mention belongs to comment
     * @return type
     */
    public function comment() {
        return $this->belongsTo('App\Models\PostCoamment', 'post_comments_id', 'id');
    }

    /**
     * insert mention users array / save mention users
     * @param type $mentions
     * @return type
     */
    public static function saveMentions($mentions) {
        return CommentMentionUser::insert($mentions);
    }

    /**
     * get comment mention users by comment id
     * @param type $comment_id
     * @return type
     */
    public static function getCommentMentionsByComment($comment_id) {
        if ($result = static::select("id","user_posts_id","post_comments_id","user_id","created_at")->where('post_comments_id', '=', $comment_id)->get()) {
            return $result->toArray();
        }

        return [];
    }

    /**
     * delete comment mentions by comment id
     * @param type $comment_id
     * @return type
     */
    public static function deleteCommentMentionsByComment($comment_id) {
        return static::where('post_comments_id', '=', $comment_id)->delete();
    }

}
