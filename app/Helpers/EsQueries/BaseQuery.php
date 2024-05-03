<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers\EsQueries;

use Elasticsearch\ClientBuilder;

/**
 * Description of BaseQuery
 *
 * @author rizwan
 */
class BaseQuery {

    use \App\Traits\RedisTrait;

    /**
     * Get elasticsearch client
     * @return type
     */
    public function getEsClient() {
        return ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
    }

    public function prepareEsBaseQuery($index = 'trending', $query) {
        return [
            'index' => $index,
            'type' => 'doc',
            'routing' => 1,
            'body' => $query
        ];
    }
    
   

    /**
     * Prepare object ID
     * @param type $object_id
     * @param type $type
     * @return type
     */
    public function prepareObjectId($object_id = 0, $prefix = 'u') {
        return "$prefix-$object_id";
    }

    public function postSourceAttrs() {
        return [
            "id", "short_code", "db_id", "created_at", "post_attributes.*", "post_type_id",
            "post_media.*", "created_location", "place", "post_location"
        ];
    }

    public function userSourceAttrs() {
        return ["id", "uid", "is_live", "username", "full_name", "picture", "bucket"];
    }

    /**
     * Manage post types condition
     * @param type $types
     * @return type
     */
    public function postTypeCondtion($types = ['A']) {
        return [
            "has_child" => [
                "type" => "post_box",
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "terms" => [
                                    "status" => $types
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Prepare user followers condition
     * @param type $user_id
     * @return type
     */
    public function prepareUserFollowerCondition($user_id) {
        return [
            [
                "term" => [
                    "user_id" => $this->prepareObjectId($user_id, 'u')
                ]
            ],
            [
                "term" => [
                    "status" => "A"
                ]
            ]
        ];
    }

    public function getTopTrendingPost($user_id) {
        if ($tt = $this->getTopTrendingPosts()) {
            if (!$user_trending = $this->getUserUsedTopTrending($user_id)) {
                $user_trending = [];
            }
            $res = array_values(array_diff($tt, $user_trending));
            //if nothing is present in user Top Trending
            $tt_post = 0;
            if (empty($res) || count($res) == count($tt)) {
                $tt_post = $tt[0];
                $user_trending = [$tt[0]];
            } else {
                $user_trending[$res[0]] = $res[0];
                $user_trending = array_values($user_trending);
                $tt_post = $res[0];
            }
            $this->cacheUserHomeTopTrendPosts($user_id, $user_trending);
            return $tt_post;
        } else {
            return 0;
        }
    }

    private function getUserUsedTopTrending($user_id) {
        $data = $this->getUserHomeTopTrendPosts($user_id);
        if (is_array($data) && !empty($data)) {
            return array_values($data);
        }
        return [];
    }

    public function getTopTrendingPosts() {
        $post_ids = [];
        if ($data = $this->getTopTrendPostsHome()) {
            $post_ids = $data;
        } else {
            $query = [
                "size" => 500,
                "_source" => ["db_id"],
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "term" => [
                                    "type" => "post"
                                ]
                            ],
                            [
                                "term" => [
                                    "status" => "TT"
                                ]
                            ]
                        ]
                    ]
                ],
                "sort" => [["updated_at" => ["order" => "desc"]]]
            ];
            $query = $this->prepareEsBaseQuery('trending', $query);
            $result = $this->getEsClient()->search($query);
            if ($result["hits"]["total"] > 0) {
                $post_ids = array_column(array_column($result["hits"]["hits"], "_source"), "db_id");
                $this->cacheTopTrendPostsHome($post_ids);
            }
        }
        return $post_ids;
    }

    /**
     * cast Array values to integers
     * @param type $rows
     * @return type
     */
    public function castValueToInteger($rows = []) {
        foreach ($rows as $key => $val) {
            $rows[$key] = (int) $val;
        }
        return $rows;
    }

}
