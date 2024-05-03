<?php

class PostObserver extends BaseObserver {

//    public $parentMapping = [
//        'parent' => 'user',
//        'prefix' => 'u',
//        'field' => ['name' => 'user_id', 'prefix' => 'u'],
//        'prefixes' => ['user_id' => 'u'],
//    ];
//    public $fields = ['id' => 'id', 'user_id' => 'user_id', 'text_content' => 'content', 'post_type_id' => 'post_type_id', 'created_at' => 'created_at', 'updated_at' => 'updated_at'
//        , 'score' => 'score', 'local_db_path' => 'local_db_path', 'client_ip_latitude' => 'client_ip_latitude', 'client_ip_longitude' => 'client_ip_longitude'];
//    public $geoLocFields = [
//        "location" => [
//            "latitude" => "client_ip_latitude",
//            "longitude" => "client_ip_longitude"
//        ]
//    ];
//    public $documentType = 'post';
//    public $modalPrefix = 'p';
//    public $childRelations = [
//        "post" => "p",
//        "nested" => [
//            "likes" => [
//                "key" => "object_id",
//                "prefix" => "p"
//            ],
//            "user_likes" => [
//                "key" => "object_id",
//                "prefix" => "p"
//            ],
//            "views" => [
//                "key" => "object_id",
//                "prefix" => "p"
//            ],
//        ]
//    ];
//
//    public function created($model) {
//        parent::created($model);
//    }
//
//    public function updated($model) {
//        if ($model->archive) {
//            parent::deleted($model);
//        } else {
//
//            parent::updated($model);
//        }
//    }
//
//    public function deleted($model) {
//        parent::deleted($model);
//        
//    }

//    public function prepareData($model) {
//        return parent::prepareData($model);
//    }
}
