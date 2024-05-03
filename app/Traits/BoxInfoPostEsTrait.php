<?php

namespace App\Traits;

/**
 * Description of BoxInfoPostEsTrait
 *
 * @author Ali
 */
trait BoxInfoPostEsTrait {

    public $esProfileIndex = "profile-boxes-cache";
    public $esIndex = "trending";

    /**
     * 
     * @param type $box_ids
     * @param type $id
     * @return array
     */
    public function getBoxesInformationQuery($box_ids, $id = 0) {
        $params = [
            'index' => $this->esIndex,
            'type' => 'doc',
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "term" => [
                                    "type" => "box"
                                ]
                            ],
                            [
                                "terms" => [
                                    "db_id" => $box_ids
                                ]
                            ]
                        ]
                    ]
                ],
                "_source" => false,
                "size" => 0,
                "aggs" => [
                    "posts" => [
                        "nested" => [
                            "path" => "box_posts"
                        ],
                        "aggs" => [
                            "filtered" => [
                                "filter" => [
                                    "bool" => $this->getPostMustCondtion($id),
                                ],
                                "aggs" => [
                                    "group_max_by_box_id" => [
                                        "terms" => [
                                            "field" => "box_posts.box_id"
                                        ],
                                        "aggs" => [
                                            "max_date" => [
                                                "max" => [
                                                    "field" => "box_posts.created_at"
                                                ]
                                            ]
                                        ]
                                    ],
                                    "group_by_count_by_box_id" => [
                                        "terms" => [
                                            "field" => "box_posts.box_id"
                                        ],
                                        "aggs" => [
                                            "post_count" => [
                                                "cardinality" => [
                                                    "field" => "box_posts.id"
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $params;
    }

    /**
     * Fetch box ids with latest content
     * @param type $box_ids
     * @return type
     */
    public function getLatestBoxPostQuery($box_ids) {
        return [
            "index" => $this->esIndex,
            "type" => "doc",
            "body" => [
                "_source" => false,
                "size" => count($box_ids),
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "term" => [
                                    "type" => "box"
                                ]
                            ],
                            [
                                "terms" => [
                                    "db_id" => $box_ids,
                                ]
                            ],
                            [
                                "nested" => [
                                    "path" => "box_posts",
                                    "inner_hits" => [
                                        "size" => 4,
                                        "_source" => [
                                            "box_posts.created_at",
                                            "box_posts.post_media.file",
                                            "box_posts.post_media.file_base_name",
                                            "box_posts.post_media.file_type_number",
                                            "box_posts.post_media.bucket"
                                        ],
                                        "sort" => [
                                            [
                                                "box_posts.id" => [
                                                    "order" => "desc"
                                                ]
                                            ]
                                        ]
                                    ],
                                    "query" => [
                                        "bool" => [
                                            "must" => []
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Will help above method
     * @param type $id
     * @return type
     */
    private function getPostMustCondtion($id) {
        if ($id > 0) {
            return [
                "must_not" => [
                    "term" => [
                        "box_post.id" => $id
                    ]
                ]
            ];
        }
        return [
            "must" => [
            ]
        ];
    }

    /**
     * 
     * @param type $box_ids
     * @param type $boxesInfo
     * @return type
     */
    public function getUpdateProfileBoxCacheParam($box_ids, $boxesInfo, $is_update = true) {

        $params = [];
        foreach ($box_ids as $box_id) {
            $params['body'][] = [
                'update' => [
                    '_index' => $this->esProfileIndex,
                    '_type' => 'doc',
                    "_id" => $box_id,
                    "routing" => 1
                ]
            ];

            $params['body'][] = [
                'doc' => [
                    'is_update' => $is_update,
                    'indexed_at' => !empty($boxesInfo["max_dates"][$box_id]) ? $boxesInfo["max_dates"][$box_id] : "",
                    'post_count' => !empty($boxesInfo["post_counts"][$box_id]) ? $boxesInfo["post_counts"][$box_id] : 0
                ]
            ];
        }

        return $params;
    }

    /**
     * 
     * @param type $box_ids
     * @param type $boxesInfo
     * @return type
     */
    public function getUpdateProfileBoxCacheParamWithMedia($box_ids, $boxesInfo) {

        $params = [];
        foreach ($box_ids as $box_id) {
            $params['body'][] = [
                'update' => [
                    '_index' => $this->esProfileIndex,
                    '_type' => 'doc',
                    "_id" => $box_id,
                    "routing" => 1
                ]
            ];
            $params['body'][] = ["doc" => $boxesInfo[$box_id]];
        }

        return $params;
    }

    /**
     * Will convert the the boxes post count and last post date 
     * @param type $res
     * @return type
     */
    public function getBoxesMeaningFullData($res) {
        $data = [];
        if (!empty($res["aggregations"]["posts"]["filtered"])) {
            if (!empty($res["aggregations"]["posts"]["filtered"]["group_max_by_box_id"]["buckets"])) {
                $data["max_dates"] = $this->performLoopOnBoxInformation($res, "group_max_by_box_id", "max_date");
            }
            if (!empty($res["aggregations"]["posts"]["filtered"]["group_by_count_by_box_id"]["buckets"])) {
                $data["post_counts"] = $this->performLoopOnBoxInformation($res, "group_by_count_by_box_id", "post_count");
            }
        }
        return $data;
    }

    /**
     * It will perform on loop on above function
     * @param type $bucket_key
     * @param type $field_key
     * @return type
     */
    private function performLoopOnBoxInformation($res, $bucket_key, $field_key) {
        $data = [];
        foreach ($res["aggregations"]["posts"]["filtered"][$bucket_key]["buckets"] as $bucket) {
            $data[$bucket["key"]] = isset($bucket[$field_key]["value_as_string"]) ? $bucket[$field_key]["value_as_string"] : $bucket[$field_key]["value"];
        }
        return $data;
    }

    /**
     * 
     * @param type $user_id
     * @return type
     */
    public function getProfileDeleteByQuery($user_id) {

        return [
            'index' => $this->esProfileIndex,
            'type' => 'doc',
            "client" => [
                'future' => 'lazy'
            ],
            'body' => [
                'query' => [
                    'term' => [
                        'user_id' => $user_id
                    ]
                ]
            ]
        ];
    }

}
