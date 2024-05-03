<?php

namespace App\Helpers\EsQueries;

/**
 * Description of IsFayvedBuild
 *
 * @author rizwan
 */
use App\Helpers\EsQueries\BaseQuery;

class IsFayvedBuild extends BaseQuery {

    use \App\Traits\RedisTrait;

    private $esIndex = "trending";

    public function __construct() {
        
    }

    public function processIsFayved($user_id = 0, $posts = []) {
        try {
            $buckets = [];

            if ($source_ids = $this->extractSourceIds($posts)) {
                $query = $this->prepareEsQuery($user_id, $source_ids);
                $query = $this->prepareEsBaseQuery($this->esIndex, $query);

                $result = $this->getEsClient()->search($query);

                if (isset($result["aggregations"]["fayys"]) && !empty($result["aggregations"]["fayys"]["buckets"])) {
                    $buckets = $result["aggregations"]["fayys"];
                    $buckets = $this->shiftArrayToKeyValue('key', $buckets['buckets']);
                }
            }

            return $this->mergedIsFayved($posts, $buckets);
        } catch (\Exception $ex) {
            $log["method"] = "IsFayvedBuild-processIsFayved";
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return $posts;
        }
    }

    private function extractSourceIds($posts) {
        $source_ids = [];
        foreach ($posts as $key => $post) {
            if ($post["post_type"] == config("general.post_type_index.api")) {
                $source_ids[] = $this->prepareSourceId($post["api_attributes"]);
            }
        }
        return array_values(array_unique($source_ids));
    }

    /**
     * Prepare Source id
     * @param type $attr
     * @return type
     */
    private function prepareSourceId($attr) {
        $id = "{$attr["source_type"]}-{$attr["source_id"]}@{$attr["item_type_number"]}";
        return (string) $id;
    }

    private function prepareEsQuery($user_id = 0, $source_ids = []) {
        $query = [
            "size" => 0,
            "_source" => false,
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => ["type" => "post"]
                        ],
                        [
                            "term" => ["post_type_id" => config("general.post_type_index.api")]
                        ],
//                        [
//                            "term" => ["user_id" => $this->prepareObjectId($user_id, 'u')]
//                        ],
                        [
                            "terms" => ["source_id" => $source_ids]
                        ]
                    ]
                ]
            ],
            "aggs" => [
                "fayys" => [
                    "terms" => [
                        "size" => count($source_ids),
                        "field" => "source_id"
                    ],
                    "aggs" => [
                        "is_fayved" => [
                            "filter" => [
                                "term" => ["user_id" => $this->prepareObjectId($user_id, 'u')
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $query;
    }

    /**
     * Merge is fayved bit
     * @param boolean $posts
     * @param type $buckets
     * @return boolean
     */
    private function mergedIsFayved($posts, $buckets = []) {
        foreach ($posts as $key => $post) {
            if ($post["post_type"] == config("general.post_type_index.search")) {
                $source_id = $this->prepareSourceId($post["api_attributes"]);

                if (isset($buckets[$source_id])) {
                    $posts[$key]["api_attributes"]["fayvs"] = (int) $buckets[$source_id]['doc_count'];
                    $posts[$key]["api_attributes"]["is_fayved"] = ($buckets[$source_id]['is_fayved']['doc_count'] > 0) ? true : false;
                } else {
                    $posts[$key]["api_attributes"]["fayvs"] = 0;
                    $posts[$key]["api_attributes"]["is_fayved"] = false;
                }
            }
        }
        return $posts;
    }

    private function shiftArrayToKeyValue($col_key = "", $rows = []) {
        $response = [];
        foreach ($rows as $key => $row) {
            $response[$row[$col_key]] = $row;
        }
        return $response;
    }

    /**
     * GET Fayved count of post
     * @param type $post
     * @return int
     */
    public function getFayvedCount($user_id = 0, $post, $default_fayvs = 1) {
        try {
            $source_ids = [$this->prepareSourceId($post)];
            $query = $this->prepareEsQuery($user_id, $source_ids);
            $params = $this->prepareEsBaseQuery($this->esIndex, $query);
            $result = $this->getEsClient()->search($params);
            if (isset($result["aggregations"]["fayys"]) && !empty($result["aggregations"]["fayys"]["buckets"])) {
                return [
                    "fayvs" => $result["aggregations"]["fayys"]["buckets"][0]["doc_count"],
                    "is_fayved" => $result["aggregations"]["fayys"]["buckets"][0]["is_fayved"]["doc_count"] > 0 ? true : false,
                ];
            } else {
                return ["fayvs" => $default_fayvs, "is_fayved" => false];
            }
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return ["fayvs" => $default_fayvs, "is_fayved" => false];
        }
    }

}
