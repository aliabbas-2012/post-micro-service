<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Redis;

class User extends Model implements AuthenticatableContract, AuthorizableContract {

    use Authenticatable,
        Authorizable,
        HasApiTokens;

    use \App\Traits\BucketPathTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primarykey = 'id';
    protected $fillable = [
        'id', 'uid', 'role_id', 'facebook_id', 'phone',
        'email', 'username', 'password',
        'full_name', 'about', 'picture',
        'gender', 'is_private', 'verification_code',
        'user_token', 'archive', 'created_at',
        'updated_at', 'website', 'location',
        'age', 'email_confirmed',
        'score', 'is_chat_notification', 'message_privacy',
        'api_version', 'time_zone', 'ip_address',
        'latitude', 'longitude', 'login_country_id',
        'login_city', 'is_indexed', 'is_updated_api', "short_code",
        'is_es_synced',"level"
    ];
    public $appends = ['thumb', 'original', 'medium', 'is_private'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function isFollowedAll() {
        return $this->hasOne('App\Models\Friend', 'following_id', 'id')
                        ->selectRaw('following_id,count(*) as following_count,status')
                        ->groupBy('following_id');
    }

    public function isFriend() {
        return $this->hasOne('App\Models\Friend', 'following_id', 'id')
                        ->selectRaw('following_id,count(*) as following_count,status')
                        ->groupBy('following_id');
    }

    public function isFollowed() {
        return $this->hasOne('App\Models\Friend', 'following_id', 'id');
    }

    public static function getUserByToken($uid, $columns = []) {
        if (!empty($columns)) {
            return static::select($columns)->where('uid', '=', $uid)->first();
        }
        return static::where('uid', '=', $uid)->first();
    }

    public static function getUsersByUid($uids) {
        return static::whereIn('uid', $uids)->get()->toArray();
    }

    /**
     * Get users by id
     * @param type $ids
     * @return type
     */
    public static function getUsersByIds($ids) {
        $result = static::select('id', 'uid', 'username', 'picture', 'bucket')->where(function ($sql) use ($ids) {
                    $sql->whereIn('id', $ids);
                    $sql->where('archive', '=', false);
                })->orderByRaw(self::searchIdsOrder($ids))->get();
        return !$result->isEmpty() ? $result->toArray() : [];
    }

    public static function getUsersByUsername($usernames) {
        return static::whereIn('username', $usernames)->get()->toArray();
    }

    public function getIsPrivateAttribute() {

        if (isset($this->attributes['is_live'])) {
            return !boolval($this->attributes['is_live']);
        }
    }

    /**
     * Vaidate that picture is URL
     * @param type $picture
     * @return boolean
     */
    public function isValidUrl() {
        if (isset($this->attributes['picture']) && filter_var($this->attributes['picture'], FILTER_VALIDATE_URL)) {
            return true;
        }
        return false;
    }

    /**
     * Prepare user profile image URL
     * @param type $type
     * @return string
     */
    public function getProfileImageUrl($type) {
        $url = "";
        if (isset($this->attributes['picture']) && !empty($this->attributes["bucket"])) {
            $url = $this->getUrl($type);
        }
        return $url;
    }

    /**
     * Get picture URl
     * @param type $type
     * @return type
     */
    public function getUrl($type) {
        if ($this->isValidUrl()) {
            return $this->attributes['picture'];
        }

        $picture = empty($this->attributes['picture']) ? "profile_default.jpg" : $this->attributes['picture'];
        return $this->getUserCdnThumbUrl($this->attributes["bucket"], $type) . $picture;
    }

    /**
     *
     * @return type
     */
    public function getThumbAttribute() {
        return $this->getProfileImageUrl('thumb');
    }

    public function getOriginalAttribute() {
        return $this->getProfileImageUrl('original');
    }

    public function getMediumAttribute() {
        return $this->getProfileImageUrl('medium');
    }

    /**
     * Set query order by by ids array
     * @param type $Ids
     * @return type
     */
    public static function searchIdsOrder($Ids) {
        $orderBy = implode(",", $Ids);
        return \DB::raw("FIELD(id, $orderBy)");
    }

    /**
     * 
     * @param type $crit
     * @return type
     */
    public static function getUserForAppendingInCache($crit) {
        $columns = ["id", "uid", "username", "dob", "picture", "bucket",
            "full_name", "about", "gender", "is_live", "website", "location",
            "phone", "email",
            "login_city", "message_privacy","level"
        ];
        if ($user = User::select($columns)->where($crit)->first()) {
            $user->appends = [];
            return $user;
        }
        return [];
    }

    /**
     * 
     * @param type $user_ids
     * @return type
     */
    public static function getCachedUsers($user_ids) {
        $arr = [];
        $redis = Redis::connection('user_data');
        $super_key = config("general.redis_keys.users");
        if (!empty($user_ids)) {
            if ($mGet = array_filter($redis->hMGet($super_key, $user_ids))) {
                foreach ($mGet as $data) {
                    $user = json_decode($data, true);
                    $arr[$user["id"]] = $user;
                }
            }
        }

        if ($user_ids = array_diff($user_ids, array_keys($arr))) {
            $columns = ["id", "uid", "username", "dob", "picture", "bucket",
                "full_name", "about", "gender", "is_live", "website", "location",
                "phone", "email", "short_code",
                "login_city", "message_privacy", "archive", "is_verified","level"
            ];
            $users = self::select($columns)->whereIn("id", $user_ids)->orderBy("id", "ASC")->get()->toArray();

            foreach ($users as $user) {
                $arr[$user["id"]] = $user;
            }

            if (!empty($arr)) {
                foreach ($arr as $user_id => $user) {
                    $hmArr[$user_id] = json_encode($user);
                }
            }

            if (!empty($hmArr)) {

                $redis->hmset($super_key, $hmArr);
//                $redis->expire($super_key, 30); //30 Seconds
                $redis->expire($super_key, 172800); //30 Seconds
                //convert to 5 or 10 seconds
            }
        }

        return $arr;
    }

}
