<?php

namespace App\Http\EsQueries;

/**
 * Purpose of this class to handle long ES Queries against model
 * @author ali
 */
class BoxPostGroup {

    use \App\Traits\BoxPostGroupTrait;

    protected $boxPermission = [], $user_id = 0, $less_than;
    protected $boxes_limit = 200;
    protected $posts_limit = 30;
    protected $box_offset = 0;
    protected $lazy = true;

    /**
     * Separations
     */
    public function __construct() {
        $argv = func_get_args();

        switch (func_num_args()) {

            case 1:
                self::__construct1($argv[0]);
                break;
            case 2:
                self::__construct2($argv[0], $argv[1]);
                break;
            case 3:

                self::__construct2($argv[0], $argv[1], $argv[2]);
                break;
            case 4:

                self::__construct3($argv[0], $argv[1], $argv[2], $argv[3]);
                break;
            default:
                echo "None";
        }
    }

    public function __construct1($user_id, $boxPermissions = array()) {
        $this->boxPermission = $boxPermissions;
        $this->user_id = $user_id;
        $this->setBoxesLimit();
    }

    public function __construct2($user_id, $boxPermissions = array(), $less_than = 0) {
        $this->boxPermission = $boxPermissions;
        $this->user_id = $user_id;
        $this->less_than = $less_than;
        $this->setBoxesLimit();
    }

    public function __construct3($user_id, $boxPermissions = array(), $less_than = 0, $box_offset = 0) {

        $this->boxPermission = $boxPermissions;
        $this->user_id = $user_id;
        $this->less_than = $less_than;
        $this->box_offset = $box_offset;

        $this->setBoxesLimit();
    }

    /**
     * if personal profile than limit is 200 other wise 20
     */
    private function setBoxesLimit() {
        if (count($this->boxPermission) < 3) {
            $this->boxes_limit = 20;
        }
    }

    /**
     * This methods will only use for Preparing Cache
     * There will be no check applied for box Permission here
     * @param type $client
     * @return type
     */
    public function getBoxes() {

        $params = $this->prepareIndexQuery();
        $params["body"] = $this->prepareBoxAndBoxPostQuery();

        return $params;
    }

    /**
     * This methods will only use for Preparing Cache
     * There will be no check applied for box Permission here
     * @return type
     */
    public function getBoxPost() {
        $params = $this->prepareIndexQuery();
        $params["body"] = $this->prepareBoxAndBoxPostQuery("box_posts");
        return $params;
    }

    /**
     * 
     * @param type $client
     * @return type
     */
    public function getLatestPosts() {
        $params = $this->prepareIndexQuery();
        $params["body"] = $this->prepareBoxAggQuery("aggs");

        return $params;
    }

    /**
     * 
     * @param type $client
     * @return type
     */
    public function getPostCount() {
        $params = $this->prepareIndexQuery();

        $params["body"] = $this->prepareBoxAggQuery("post_count");
        $params["body"]["aggs"] = $this->preparePostCount();

        return $params;
    }

    protected function prepareIndexQuery() {
        $query = [
            'index' => 'trending',
            'type' => 'doc'
        ];
        if ($this->lazy) {
            $query['client'] = [
                'future' => 'lazy'
            ];
        }
        return $query;
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

        return $query;
    }

    /**
     * 
     * This method will only be used for preparing Cache
     * @param type $user_id
     * @param type $is_self
     * @return array
     */
    protected function prepareBoxAndBoxPostQuery($type = "box") {

        $body = [
            "from" => $this->box_offset,
            "size" => $this->boxes_limit,
            "query" => $this->prepareBaseConditon(),
        ];

        if ($type == "box") {
            $body["_source"] = ["db_id", "name", "status", "created_at", "user_id"];
        } else if ($type == "box_posts") {
            $body["_source"] = false;
            $body["query"]["bool"]["must"][] = $this->prepareNestedPostQuery();
        }

        if (count($this->boxPermission) < 3 && !empty($this->boxPermission)) {
            $body["query"]["bool"]["must"][] = $this->prepareBoxPermission();
        }
        $body["sort"] = $this->prepareSortQuery();
        $body["query"]["bool"]["must"] = array_filter($body["query"]["bool"]["must"]);

        return $body;
    }

    /**
     * Only For aggregation
     * @param type $type
     * @return type
     */
    public function prepareBoxAggQuery($type) {
        $query = [
            "query" => $this->prepareBoxBaseAggQuery(),
            "_source" => false,
            "size" => 0
        ];
        if ($type == "aggs") {
            $query["aggs"] = $this->prepareBaseAggriation();
        }

        return $query;
    }

    /**
     * Only For aggregation
     * @return type
     */
    public function prepareBoxBaseAggQuery() {
        $query = $this->prepareBaseConditon();
        $query["bool"]["must"][] = $this->prepareBoxPermission();
        $query["bool"]["must"] = array_filter($query["bool"]["must"]);

        return $query;
    }

    /**
     * 
     * @return type
     */
    protected function prepareBoxPermission() {
        return [
            "terms" => ["status" => $this->boxPermission]
        ];
    }

    /**
     * 
     * @return type
     */
    protected function prepareNestedPostQuery() {
        return [
            "bool" => [
                "must" => [
                        [
                        "nested" => [
                            "path" => "box_posts",
                            "query" => [
                                "bool" => [
                                    "must" => []
                                ]
                            ],
                            "inner_hits" => $this->prepareInnerHits()
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function prepareInnerHits() {
        return [
            "sort" => [
                    [
                    "box_posts.id" => [
                        "order" => "desc"
                    ]
                ]
            ],
            "size" => 4,
            "_source" => [
                "box_posts.id",
                "box_posts.post_type_id",
                "box_posts.post_attributes",
                "box_posts.location",
                "box_posts.created_at",
                "box_posts.post_media.file",
                "box_posts.post_media.bucket",
                "box_posts.post_media.bg_color",
                "box_posts.post_media.file_type_number",
            ]
        ];
    }

    protected function prepareBaseAggriation() {
        return [
            "posts" => [
                "nested" => [
                    "path" => "box_posts"
                ],
                "aggs" => [
                    "filtered" => [
                        "filter" => [
                            "bool" => [
                                "must" => $this->prepareAggRangeQuery()
                            ]
                        ],
                        "aggs" => [
                            "top_posts" => [
                                "terms" => [
                                    "field" => "box_posts.id",
                                    "size" => $this->posts_limit,
                                    "order" => [
                                        "created_at_order" => "desc"
                                    ]
                                ],
                                "aggs" => $this->preparePostAggrigationQuery()
                            ]
                        ]
                    ],
                ]
            ]
        ];
    }

    public function preparePostAggrigationQuery() {
        return [
            "top_posts" => [
                "top_hits" => [
                    "sort" => [
                            [
                            "box_posts.created_at" => [
                                "order" => "desc"
                            ]
                        ]
                    ],
                    "_source" => [
                        "includes" => $this->getBoxAggFields()
                    ],
                    "size" => 1
                ]
            ],
            "created_at_order" => [
                "max" => [
                    "field" => "box_posts.created_at"
                ]
            ]
        ];
    }

    protected function prepareAggRangeQuery() {
        if ($this->less_than > 0) {
            return [
                    [
                    "range" => [
                        "box_posts.id" => [
                            "lt" => $this->less_than
                        ]
                    ]
                ]
            ];
        }
        return [];
    }

    protected function preparePostCount() {
        return ["posts" => [
                "nested" => [
                    "path" => "box_posts"
                ],
                "aggs" => [
                    "post_count" => [
                        "cardinality" => [
                            "field" => "box_posts.id"
                        ]
                    ]
                ]
        ]];
    }

    protected function prepareSortQuery() {
        return [
                [
                "box_posts.id" => [
                    "mode" => "max",
                    "nested" => [
                        "path" => "box_posts"
                    ],
                    "order" => "desc"
                ]
            ]
        ];
    }

}
