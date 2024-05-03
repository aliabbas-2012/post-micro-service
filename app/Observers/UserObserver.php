<?php
namespace App\Observers;

use App\Observers\BaseObserver;

class UserObserver extends BaseObserver {

//    public $fields = [
//        'id' => 'id', 'facebook_id' => 'facebook_id', 'username' => 'username',
//        'full_name' => 'full_name', 'is_live' => 'is_live', 'score' => 'score', 'message_privacy' => 'message_privacy',
//        'created_at' => 'created_at', 'updated_at' => 'updated_at', 'latitude' => 'latitude', 'longitude' => 'longitude'
//    ];
//    public $geoLocFields = [
//        "location" => [
//            "latitude" => "latitude",
//            "longitude" => "longitude"
//        ]
//    ];
//    public $documentType = 'user';
//    public $modalPrefix = 'u';
//    public $childRelations = [
//        "user" => "u",
//        "nested" => [
//            "post" => [
//                "key" => "object_id",
//                "prefix" => "p"
//            ],
//            "user_likes" => [
//                "key" => "user_id",
//                "prefix" => "u"
//            ],
//            "followers" => [
//                "key" => "object_id",
//                "prefix" => "u"
//            ],
//            "followings" => [
//                "key" => "user_id",
//                "prefix" => "u"
//            ],
//            "block" => [
//                "key" => "user_id",
//                "prefix" => "u"
//            ],
//            "blocked" => [
//                "key" => "object_id",
//                "prefix" => "u"
//            ],
//            "children" => [
//                "likes" => [
//                    "parent" => "post",
//                    "field" => "user_id",
//                    "prefix" => "u"
//                ],
//                "views" => [
//                    "parent" => "post",
//                    "field" => "user_id",
//                    "prefix" => "u"
//                ],
//            ]
//        ]
//    ];
//
//    public function created($model) {
//        $model->is_live = boolval($model->is_private);
//        parent::created($model);
//    }
//
//    public function updated($model) {
//        if ($model->archive) {
//            parent::deleted($model);
//        } else {
//            $model->is_live = boolval($model->is_private);
//            parent::updated($model);
//        }
//    }
//
//    public function deleted($model) {
//        parent::deleted($model);
//    }

//    public function prepareData($model) {
//        return parent::prepareData($model);
//        
//    }
}
