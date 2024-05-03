<?php

namespace App\Helpers\EsQueries;

/**
 * Description of Trending
 *
 * @author rizwan
 */
class FriendPost extends BaseQuery {

    public $greater_than = 0;
    public $less_than = 0;
    public $user_id = 0;
    public $top_post = 0;
    public $limit = 40;
    public $offset = 40;
    public $es_index = 'trending';
    public $es_indexes = 'trending,interest_history';
    public $timestamp = null;
    public $inputs = [];

    public function __construct($user_id = 0, $inputs = []) {
        $this->less_than = isset($inputs['less_than']) ? $inputs['less_than'] : 0;
        $this->greater_than = isset($inputs['greater_than']) ? $inputs['greater_than'] : 0;
        $this->limit = isset($inputs['limit']) ? $inputs['limit'] : 40;
        $this->offset = isset($inputs['api_offset']) ? $inputs['api_offset'] : 0;
        $this->user_id = $user_id;
        $this->timestamp = $inputs['timestamp'];
        $this->top_post = isset($inputs['tt']) ? $inputs['tt'] : 0;
        $this->inputs = $inputs;
    }

    /**
     * Get trending post for guest user
     * @param type $device
     * @param type $session
     * @return type....
     */
    public function getFriendsPosts($user_interests = []) {
        try {
            $response = [];
            if ($this->greater_than > 0) {
                return []; // Temporarily solution for existing running apps
            }
            $user_interests = empty($user_interests) ? [0] : $user_interests;
            $query = $this->prepareFriendsEsQuery($user_interests);
            \Log::info("---- Discover friends posts query ------");
            \Log::info(json_encode($query));

            $query = $this->prepareEsBaseQuery($this->es_index, $query);
            $result = $this->getEsClient()->search($query);
            if (isset($result['hits']['hits']) && count($result['hits']['hits']) > 0) {
                $response = $result['hits']['hits'];
            }
            return $response;
        } catch (\Exception $ex) {
            $exception['method'] = "getGuestTrendingPosts";
            $exception['message'] = $ex->getMessage();
            $exception['file'] = $ex->getFile();
            $exception['line'] = $ex->getLine();
            \Log::info($exception);
            return [];
        }
    }

    /**
     * Prepare user friends post es query
     * @return type
     */
    private function prepareFriendsEsQuery($user_interests = []) {
        $query = [
            "from" => (int) $this->offset,
            "size" => (int) $this->limit,
            "_source" => $this->postSourceAttrs(),
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "type" => "post"
                            ]
                        ],
                        [
                            "has_parent" => [
                                "parent_type" => "user",
                                "inner_hits" => [
                                    "size" => 1,
                                    "_source" => $this->userSourceAttrs()
                                ],
                                "query" => [
                                    "bool" => [
                                        "must" => [
                                            [
                                                "has_child" => [
                                                    "type" => "followers",
                                                    "query" => [
                                                        "bool" => [
                                                            "must" => $this->prepareUserFollowerCondition($this->user_id)
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        $this->getRangeCondition(),
                        $this->postTypeCondtion(["A", "F"]),
                        $this->prepareHomeScoring($user_interests)
                    ]
                ]
            ],
//            "sort" => [
//                ["db_id" => ["order" => "desc"]]
//            ]
        ];
        return $query;
    }

    /**
     * Prepare post range condition
     */
    private function getRangeCondition() {
        return [
            "range" => [
                "created_at" => [
                    "lte" => $this->timestamp
                ]
            ]
        ];

//        $range = ["range" => ["db_id" => ["gt" => 0]]];
//        if ($this->greater_than > 0 && $this->less_than <= 0) {
//            $range = [
//                "range" => [
//                    "db_id" => [
//                        "gt" => (int) $this->greater_than
//                    ]
//                ]
//            ];
//        } else if ($this->less_than > 0) {
//            $range = [
//                "range" => [
//                    "db_id" => [
//                        "lt" => (int) $this->less_than
//                    ]
//                ]
//            ];
//        }
//        return $range;
    }

    private function prepareHomeScoring($user_interests = []) {
        return [
            "function_score" => [
                "boost" => "5",
                "max_boost" => 500,
                "score_mode" => "max",
                "boost_mode" => "multiply",
                "min_score" => 1,
                "functions" => [
                    [
                        "gauss" => [
                            "created_at" => [
                                "origin" => $this->timestamp,
                                "scale" => "1500d",
                                "decay" => "0.9",
                                "offset" => "1m"
                            ]
                        ],
                        "weight" => 500
                    ]
                ],
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "function_score" => [
                                    "boost" => 5,
                                    "max_boost" => 500,
                                    "score_mode" => "max",
                                    "boost_mode" => "multiply",
                                    "min_score" => 1,
                                    "functions" => [
                                        [
                                            "filter" => [
                                                "bool" => [
                                                    "must" => [
                                                        ["term" => ["db_id" => $this->top_post]]
                                                    ]
                                                ]
                                            ],
                                            "weight" => 500
                                        ],
                                        [
                                            "filter" => [
                                                "bool" => [
                                                    "must" => [
                                                        ["term" => ["status" => "NT"]],
                                                        ["terms" => ["categories" => $user_interests]]
                                                    ]
                                                ]
                                            ],
                                            "weight" => 400
                                        ],
                                        [
                                            "filter" => [
                                                "bool" => [
                                                    "must" => [
                                                        ["term" => ["status" => "NT"]]
                                                    ],
                                                    "must_not" => [
                                                        [
                                                            "terms" => [
                                                                "categories" => $user_interests
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            "weight" => 50
                                        ]//
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
     * Merge trending posts likes/comments/views
     * @param type $posts
     * @param type $likesComments
     * @return type
     */
    public function mergerLIkesCommentsIntrending($posts = [], $likesComments = []) {
        if (!empty($likesComments)) {
            foreach ($likesComments as $key => $val) {
                if (isset($posts[$key])) {
                    $posts[$key]['is_liked'] = $val['is_liked'];
                    $posts[$key]['reaction_id'] = (int) $val['reaction_id'];
                    $posts[$key]['likes'] = (int) $val['likes'];
                    $posts[$key]['comments'] = (int) $val['comments'];
                    $posts[$key]['views'] = (int) $val['views'];
                    if (isset($posts[$key]['media_attributes'])) {
                        $posts[$key]['media_attributes']['likes'] = (int) $val['likes'];
                        $posts[$key]['media_attributes']['comments'] = (int) $val['comments'];
                        $posts[$key]['media_attributes']['views'] = (int) $val['views'];
                    }
                }
            }
        }
        return $posts;
    }

    public function getBookmarkedPostsByids() {
        try {
            $response = [];
            $query = $this->prepareBookmarkedPostsEsQuery();
            \Log::info("---- Discover bookmark posts query ------");
            \Log::info(json_encode($query));
            $query = $this->prepareEsBaseQuery($this->es_index, $query);
            $result = $this->getEsClient()->search($query);
            if (isset($result['hits']['hits']) && count($result['hits']['hits']) > 0) {
                $response = $result['hits']['hits'];
            }
            return $response;
        } catch (\Exception $ex) {
            $exception['method'] = "getBookmarkedPostsByids";
            $exception['message'] = $ex->getMessage();
            $exception['file'] = $ex->getFile();
            $exception['line'] = $ex->getLine();
            \Log::info($exception);
            return [];
        }
    }

    private function prepareBookmarkedPostsEsQuery() {
        $query = [
            "from" => (int) $this->offset,
            "size" => (int) count($this->inputs["post_ids"]),
            "_source" => $this->postSourceAttrs(),
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "type" => "post"
                            ]
                        ],
                        [
                            "terms" => [
                                "db_id" => $this->castValueToInteger($this->inputs["post_ids"])
                            ]
                        ],
                        [
                            "bool" => [
                                "should" => [
                                    [
                                        "bool" => [
                                            "must" => [
                                                [
                                                    "has_parent" => [
                                                        "parent_type" => "user",
                                                        "inner_hits" => [
                                                            "name" => "friend_user",
                                                            "size" => 1,
                                                            "_source" => $this->userSourceAttrs()
                                                        ],
                                                        "query" => [
                                                            "bool" => [
                                                                "must" => [
                                                                    [
                                                                        "has_child" => [
                                                                            "type" => "followers",
                                                                            "query" => [
                                                                                "bool" => [
                                                                                    "must" => $this->prepareUserFollowerCondition($this->user_id)
                                                                                ]
                                                                            ]
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                $this->postTypeCondtion(["A", "F"])
                                            ]
                                        ]
                                    ],
                                    [
                                        "bool" => [
                                            "must" => [
                                                [
                                                    "has_parent" => [
                                                        "parent_type" => "user",
                                                        "inner_hits" => [
                                                            "name" => "public_user",
                                                            "size" => 1,
                                                            "_source" => $this->userSourceAttrs()
                                                        ],
                                                        "query" => [
                                                            "bool" => [
                                                                "must" => [
                                                                    [
                                                                        "term" => [
                                                                            "is_live" => true
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                $this->postTypeCondtion(["A"])
                                            ]
                                        ]
                                    ],
                                    [
                                        "bool" => [
                                            "must" => [
                                                [
                                                    "has_parent" => [
                                                        "parent_type" => "user",
                                                        "inner_hits" => [
                                                            "name" => "owner_user",
                                                            "size" => 1,
                                                            "_source" => $this->userSourceAttrs()
                                                        ],
                                                        "query" => [
                                                            "bool" => [
                                                                "must" => [
                                                                    [
                                                                        "term" => [
                                                                            "db_id" => (int) $this->user_id
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                $this->postTypeCondtion(["A", "F", "M"])
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
        return $query;
    }

}
