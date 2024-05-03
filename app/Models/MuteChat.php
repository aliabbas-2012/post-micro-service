<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MuteChat extends Model {

    public $timestamps = false;
    public $table = 'mute_chat';
    public $primarykey = 'id';
    protected $fillable = [
        'user_id',
        'sender_id',
        'created_at',
        'updated_at'
    ];

}
