<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class NotificationSetting extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notification_settings';
    protected $primarykey = 'id';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'post_comment_push_notification', 'post_comment_email_notification',
        'new_follower_push_notification', 'new_follower_email_notification',
        'like_my_post_push_notification', 'like_my_post_email_notification'];

    
    
    protected $hidden = [
        'id', 'is_cool_push_notification', 'is_cool_email_notification',
        'following_post_push_notification', 'following_post_email_notification'
    ];


    /**
     * model relashions
     * @return type
     */
    public function user() {
        return $this->belongsTo('App\Models\User', 'id', 'user_id');
    }

}
