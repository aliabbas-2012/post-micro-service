<?php

namespace App\Http\EsQueries;

/**
 * Used to Search 
 * @author ali
 */
class SearchBoxGroup extends BoxPostGroup {

    protected $search_key = "";

    public function prepareBoxAndBoxPostQuery($type = "box") {
        $body = [
            "from" => $this->box_offset,
            "size" => $this->boxes_limit,
            "query" => $this->prepareBaseConditon(),
        ];

        $body["size"] = $this->boxes_limit;
        if ($type == "box") {
            $body["_source"] = ["db_id", "name", "status", "created_at", "user_id"];
        } else if ($type == "box_posts") {
            $body["_source"] = false;
            $body["query"]["bool"]["must"][] = $this->prepareNestedPostQuery();
        }
        $body["query"]["bool"]["must"][] = $this->prepareBoxSearchQuery();
        $body["query"]["bool"]["must"][] = $this->prepareBoxPermission();
        $body["query"]["bool"]["must"] = array_filter($body["query"]["bool"]["must"]);
        
        
        return $body;
    }

    public function setSearchKey($input) {
        $this->boxes_limit = 200;
        $this->box_offset = 0;
        $this->search_key = $input["search_key"];
    }

    /**
     * Prepare Search Filter
     * @return type
     */
    private function prepareBoxSearchQuery() {
        $search =  [
            "bool" => [
                "should" => [
                    [
                        "multi_match" => [
                            "query" => $this->search_key,
                            "fields" => [
                                "name.edgengram",
                                "name.raw"
                            ]
                        ]
                    ],
                    [
                        "wildcard" => [
                            "name" => [
                                "wildcard" => "*" . strtolower($this->search_key) . "*",
                                "boost" => 2
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        return $search;
    }

}
