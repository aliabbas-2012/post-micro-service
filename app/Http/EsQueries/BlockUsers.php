<?php

namespace App\Http\EsQueries;

/**
 * Blocked Users query
 *
 * @author ali
 */
class BlockUsers {

    public function getBlockUserQuery($user_id) {
        $params = [
            'index' => 'trending',
            'type' => 'doc',
            'body' => $this->prepareBlockUserBaseQuery($user_id)
        ];
        return $params;
    }

    public function prepareBlockUserBaseQuery($user_id) {
        return [
            "_source" => [
                "user_id",
                "object_id", "type.name"
            ],
            "size" => 1000,
            "query" => [
                "bool" => [
                    "should" => [
                        $this->prepareMyBlockedListQuery($user_id),
                        $this->prepareWhoBlockedMeListQuery($user_id),
                    ]
                ]
            ]
        ];
    }

    public function prepareMyBlockedListQuery($user_id) {
        return [
            "bool" => [
                "must" => [
                    [
                        "term" => [
                            "type" => "block"
                        ]
                    ],
                    [
                        "term" => [
                            "user_id" => "u-" . $user_id
                        ]
                    ]
                ]
            ]
        ];
    }

    public function prepareWhoBlockedMeListQuery($user_id) {
        return[
            "bool" => [
                "must" => [
                    [
                        "term" => [
                            "type" => "blocked"
                        ]
                    ],
                    [
                        "term" => [
                            "object_id" => "u-" . $user_id
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    public function getBlockList($data) {
        $blocked_list = [];
        if ($data["hits"]["total"] > 0) {
            foreach ($data["hits"]["hits"] as $hit) {
                $blocked_list[] = $this->processBlockList($hit);
            }
        }
        return $blocked_list;
    }

    /**
     * Process block list
     * @param type $hit
     * @return type
     */
    public function processBlockList($hit) {
        if ($hit["_source"]["type"]["name"] == "blocked") {
            return $hit["_source"]["user_id"];
        } else if ($hit["_source"]["type"]["name"] == "block") {
            return $hit["_source"]["object_id"];
        }
    }

}
