<?php

namespace App\Http\EsQueries;

/**
 * Purpose of this class to handle long ES Queries against model
 * @author ali
 */
class BoxPosts extends BoxPostGroup {

    /**
     * 
     * @param type $inputs
     * @param type $boxPermissions
     */
    public function __construct($inputs, $boxPermissions = array()) {
        $this->boxPermission = $boxPermissions;
        $this->box_id = $inputs["box_id"];
//        $this->user_id = $inputs["user_id"];
        $this->less_than = $inputs["less_than"];
        $this->posts_limit = isset($inputs["limit"]) && $inputs["limit"] > 0 ? $inputs["limit"] : config("general.posts_limit");
        $this->lazy = false;
    }

    /**
     * This is the base condition every query required it
     * @return array
     */
    protected function prepareBaseConditon() {
        $query = [
            "bool" => [
                "must" => [
                        [
                        "term" => ["type" => "box"]
                    ]
                ]
            ]
        ];
        if ($this->user_id > 0) {
            $query["bool"]["must"][] = [
                "term" => ["user_id" => "u-" . $this->user_id]
            ];
        }
        if ($this->box_id > 0) {
            $query["bool"]["must"][] = [
                "term" => ["db_id" => $this->box_id]
            ];
        }

        return $query;
    }

}
