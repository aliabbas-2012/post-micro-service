<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers\EsQueries;

/**
 * Description of CommonPosts
 *
 * @author qadeer
 */
use Elasticsearch\ClientBuilder;

class CommonPosts {

    use \App\Traits\CommonTrait;

    /**
     * Get elasticsearch client
     * @return type
     */
    public function getEsClient() {
        return ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
    }

    public function prepareSearchIndex($body, $index = "trending", $is_lazy = false) {
        return $this->prepareEsSearchIndex($index, 'doc', $body, $is_lazy);
    }

    public function prepareEsBaseQuery($index = 'treding', $query) {
        return [
            'index' => $index,
            'type' => 'doc',
            'routing' => 1,
            'body' => $query
        ];
    }

    /**
     * 
     * @param type $posts
     * @param type $profile
     */
    public function prepareIsFayvedPosts($posts, $inputs) {
        $posts_array = [];
        if ($inputs["other_user"] === false) {
            foreach ($posts as $key => $post) {
                $posts_array[$key] = $post;
                if (!empty($post["search"])) {
                    $posts_array[$key]["search"]["is_fayved"] = true;
                }
            }
        } else {
            $posts_array = $this->fetchPostsBySource($posts, $inputs["current_user_id"]);
        }
        return $posts_array;
    }

    /**
     * 
     * @param type $posts
     * @param type $login_id
     * @return type
     */
    private function fetchPostsBySource($posts, $login_id) {

        $source_ids = array_column(array_column($posts, 'search'), 'source_id');
        if (!empty($source_ids) && count($source_ids) > 0) {
            $response = $this->getCommonPosts($login_id, $source_ids);

            if ($response["success"] == true && count($response["posts"]) > 0) {
                return $this->mergeCommonPosts($posts, array_column($response["posts"], "key"));
            }
        }
        return $posts;
    }

    /**
     * 
     * @param type $orignal_posts
     * @param type $common_posts
     * @return type
     */
    private function mergeCommonPosts($orignal_posts, $common_posts) {

        $merged_posts = [];
        foreach ($orignal_posts as $key => $post) {
            $merged_posts[$key] = $post;
            if (!empty($post["search"])) {
                $merged_posts[$key]["search"]["is_fayved"] = (bool) in_array((int) $post["search"]["source_id"], $common_posts);
            }
        }
        return $merged_posts;
    }

    /**
     * 
     * @param type $user_id
     * @param type $source_ids
     * @return type
     */
    public function getCommonPosts($user_id, $source_ids) {

        try {
            $body = [
                "_source" => false,
                "size" => 0,
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
                                    "post_type_id" => 7
                                ]
                            ],
                            [
                                "term" => [
                                    "user_id" => "u-" . $user_id
                                ]
                            ],
                            [
                                "terms" => [
                                    "post_attributes.source_id.keyword" => $source_ids
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            $aggs = $this->prepareAggregation(count($source_ids));
            $query = $this->prepareSearchIndex(array_merge($body, $aggs));
            $response = $this->getEsClient()->search($query);
            if (!empty($response["aggregations"]["posts"]["buckets"])) {
                return array('success' => true, 'posts' => $response["aggregations"]["posts"]["buckets"]);
            }
            return array('success' => false, 'posts' => []);
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . '=>' . $ex->getFile() . '(' . $ex->getLine() . ')';
            \Log::info("Excep-getUserByContacts::" . $message);
            return array('success' => false, 'posts' => [], 'message' => $ex->getMessage());
        }
    }

    /**
     * 
     * @param type $count
     * @return type
     */
    public function prepareAggregation($count) {
        return
                [
                    "aggs" => [
                        "posts" => [
                            "terms" => [
                                "size" => $count,
                                "field" => "post_attributes.source_id.keyword"
                            ]
                        ]
                    ]
        ];
    }

}
