<?php

namespace App\Helpers;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Elasticsearch\ClientBuilder;

class ElasticSearchHelper {

    private static $client = NULL;

    public function __construct() {
        
    }

    static function setClient() {
        if (NULL == self::$client) {
            self::$client = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
        }
    }

    /**
     * prepare id with prefix
     * @param type $id
     * @param type $prefix
     * @return type
     */
    static function prepareId($id, $prefix = "u") {
        return $prefix . '-' . $id;
    }

    static function prepareBody($body) {
        return [
            "index" => "trending",
            "type" => "doc",
            "body" => $body
        ];
    }

    /**
     * get people list for comment mentions
     * @param type $data
     * @return type
     */
    public function getCommentPeopleList($data) {
        self::setClient();
        try {
            $params = self::prepareBody($this->prepareCommentPeolpeBody($data));
            $response = self::$client->search($params);
            if (!empty($response["hits"]) && $response["hits"]['total'] > 0) {
                return array_column($response["hits"]['hits'], "_source");
            }
            return [];
        } catch (\Exception $ex) {
            return [];
        }
    }

    /**
     * Prepare comment people list Es query
     * @param type $data
     * @return array
     */
    private function prepareCommentPeolpeBody($data) {
        $body = [
            "from" => (int) $data["offset"],
            "size" => (int) $data["limit"],
            "sort" => [
            ],
            "_source" => ["uid", "username", "picture", "bucket", "is_verified"],
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["type" => "user"]],
                        $this->prepareCommentPeopleCondition($data),
                    ],
                    "must_not" => self::blockedUser($data['user_id'])
                ]
            ]
        ];
        return $body;
    }

    private function prepareCommentPeopleCondition($data) {
        return [
            "bool" => [
                "should" => [
                    [
                        "function_score" => [
                            "query" => $this->getCommentPeopleSearchQuery($data),
                            "boost" => "10",
                            "functions" => [
                                [
                                    "filter" => [
                                        "bool" => [
                                            "must" => [
                                                "has_child" => [
                                                    "type" => "followers",
                                                    "query" => [
                                                        "bool" => [
                                                            "must" => [
                                                                [
                                                                    "term" => [
                                                                        "user_id" => self::prepareId($data["user_id"], 'u')
                                                                    ]
                                                                ],
                                                                [
                                                                    "term" => [
                                                                        "status" => "A"
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],
                                    "weight" => 132
                                ],
                                [
                                    "filter" => [
                                        "bool" => [
                                            "must" => [
                                                "term" => [
                                                    "is_verified" => true
                                                ]
                                            ]
                                        ]
                                    ],
                                    "weight" => 44
                                ],
                                [
                                    "gauss" => [
                                        "created_location" => [
                                            "origin" => [
                                                "lon" => (float) $data["lon"],
                                                "lat" => (float) $data["lat"]
                                            ],
                                            "scale" => "20km",
                                            "offset" => "500km"
                                        ]
                                    ],
                                    "weight" => 30
                                ],
                                [
                                    "filter" => [
                                        "bool" => [
                                            "must" => [
                                                "terms" => [
                                                    "is_live" => [true, false]
                                                ]
                                            ]
                                        ]
                                    ],
                                    "weight" => 5
                                ],
                            ],
                            "max_boost" => 211,
                            "score_mode" => "max",
                            "boost_mode" => "multiply",
                            "min_score" => 211
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Blocked user validations
     * @param type $user_id
     * @return array
     */
    public static function blockedUser($user_id) {
        $blocked = [
            ['term' => ["db_id" => $user_id]],
            ['has_child' => [
                    "type" => 'block',
                    "query" => [
                        "term" => [
                            "user_id" => self::prepareId($user_id, 'u')
                        ]
                    ]
                ]
            ],
            ['has_child' => [
                    "type" => 'blocked',
                    "query" => [
                        "term" => [
                            "user_id" => self::prepareId($user_id, 'u')
                        ]
                    ]
                ]
            ]
        ];
        return $blocked;
    }

    /**
     * Prepare search body on key
     * @param type $data
     * @return array
     */
    public function getSearhQuery($data) {
        $query = [];
        if (isset($data['search_key']) && !empty($data['search_key'])) {
            $query[] = [
                "multi_match" => [
                    "query" => $data['search_key'],
                    "fields" => [
                        "username.edgengram",
                        "username.search_nGram",
                        "username.raw"
                    ],
                    "type" => "phrase"
                ]
            ];
        }
        return $query;
    }

    public function getCommentPeopleSearchQuery($data) {
        if (!isset($data['search_key']) || empty($data['search_key'])) {
            return ["match_all" => (new \stdClass())];
        } else {
            return [
                "multi_match" => [
                    "query" => $data['search_key'],
                    "fields" => [
                        "username.edgengram",
                        "username.search_nGram",
                        "username.raw"
                    ],
                    "type" => "phrase"
                ]
            ];
        }
    }

    /**
     * 
     * @param type $body
     * @return array
     */
    public function getCountAgainstQuery($body) {
        self::setClient();
        try {
            $params = self::prepareBody($body);
            $response = self::$client->count($params);
            return isset($response["count"]) ? $response["count"] : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

}
