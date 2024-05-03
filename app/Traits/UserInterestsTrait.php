<?php

namespace App\Traits;

use \App\Http\EsQueries\UserInterest as UserEsInterest;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use Elasticsearch\ClientBuilder;

/**
 * 
 * @author Rizwan
 */
trait UserInterestsTrait {

    /**
     * Order pair from customer interaction
     * @param type $user_id
     * @return type
     */
    public function getUserInterestsPair($user_id) {
        try {
            $resp = [];
            $cacheKey = config("general.redis_keys.user_top_interests") . $user_id;
            if ($data = $this->getCacheArrayByKey($cacheKey)) {
                $resp = $data;
            } else {
                $client = new Client();
                $header = [
                    "headers" => [
                        "token" => config("general.ml_url.user_signup_interest_token"),
                        "Content-Type" => "application/json",
                        "Accept" => "application/json",
                    ],
                    "body" => json_encode(["object_id" => $user_id, "object_type" => "U"]),
                ];
                $response = $client->request('GET', config("general.ml_url.user_signup_interest_pair_url"), $header);
                if ($response->getStatusCode() == 200) {

                    $model = json_decode($response->getBody()->getContents(), true);
                    $resp = array_slice($model["TI"], 0, 5);
                    $this->cacheArrayInKey($cacheKey, $resp);
                }
            }
            return $resp;
        } catch (\Exception $ex) {
            $message['method'] = 'Exception--- getUserInterestFromMl';
            $message['message'] = $ex->getMessage();
            $message['file'] = $ex->getFile();
            $message['line'] = $ex->getLine();
            \Log::info($message);
            return [];
        }
    }

    /**
     * 
     * @param type $user_id
     */
    public function getTopTrendingPost($user_id) {

        $key = config("general.redis_keys.user_top_trending") . $user_id;
        if ($tt = $this->getTopTrendingPosts()) {
            if (!$user_trending = $this->getUserUsedTopTrending($key)) {
                $user_trending = [];
            }
            $res = array_values(array_diff($tt, $user_trending));
            \Log::info("---Top Trenidng Testing------");
            \Log::info($tt);
            \Log::info($user_trending);
            \Log::info($res);
            \Log::info("---End Trenidng Testing------");

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

            Redis::set($key, json_encode($user_trending, true), 'EX', config("general.redis_keys.redis_expire_min"));
            return $tt_post;
        } else {
            return 0;
        }
    }

    private function getUserUsedTopTrending($key) {
        $data = json_decode(Redis::get($key), true);
        if (is_array($data) && !empty($data)) {
            return array_values($data);
        }
        return [];
    }

    /**
     * 
     * @return type
     */
    private function getTopTrendingPosts() {
        $trending_ids = [];
        $key = config("general.redis_keys.top_trending");
        if ($data = Redis::get($key)) {
            $trending_ids = json_decode($data);
        } else {

            $body = [
                "index" => "trending",
                "type" => "doc",
                "size" => 500,
                "_source" => ["db_id"],
                "body" => [
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
                    "sort" => [
                        ["updated_at" => ["order" => "desc"]]
                    ]
                ]
            ];
            $post_ids = [];
            $client = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
            $res = $client->search($body);

            if ($res["hits"]["total"] > 0) {
                $post_ids = array_column(array_column($res["hits"]["hits"], "_source"), "db_id");
                Redis::set($key, json_encode($post_ids), 'EX', config("general.redis_keys.redis_expire_min"));
                $trending_ids = $post_ids;
            }
        }
        return $trending_ids;
    }

}
