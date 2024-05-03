<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceToken extends Model {

    public $timestamps = false;
    protected $fillable = ['user_id', 'device_type', 'device_token', 'lang'];

    public function isMute() {
        return $this->hasOne('App\Models\MuteChat', 'user_id', 'user_id');
    }

    public function notificationSettings() {
        return $this->hasOne('App\Models\NotificationSetting', 'user_id', 'user_id');
    }

    /**
     * get device tokens by users ids
     * @param type $user_ids
     * @return type
     */
    public static function getDeviceTokensByUsers($user_ids = [], $login_id = 0, $domain = 'C') {
        $response = [];
        if (!empty($user_ids)) {
            $result = static::whereIn('user_id', $user_ids)->whereNotNull('device_token')
                            ->whereHas('notificationSettings', function($sql) use($domain) {
                                if ($domain == 'C' || $domain == 'M') {
                                    $sql->where('post_comment_push_notification', '=', true);
                                }
                            })->get();
            $response = !$result->isEmpty() ? $result : [];
        }
        return $response;
    }

}
