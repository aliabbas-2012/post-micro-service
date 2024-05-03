<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

use GuzzleHttp\Client;

/**
 * Description of GuzzleHelper
 *
 * @author rizwan & qadeer
 */
class GuzzleHelper {

    use \App\Traits\RedisTrait;

    private $client = null;
    private $locale = "en";

    public function __construct() {
        $this->client = new Client();
        $this->locale = !empty(app('translator')->getLocale()) ? app('translator')->getLocale() : "en";
    }

    private function getCommonPosts() {
        return new EsQueries\CommonPosts();
    }

    /**
     * Search user profile box from NodeJs
     * @param type $request
     * @param type $user_id
     * @param type $status
     * @param type $search_key
     * @return type
     */
    public function searchUserBoxes($request, $user_id, $status, $search_key, $offset = 0) {
        try {
            $response = ['data' => []];
            $queryString = "user_id=" . $user_id . '&status=' . $status . '&search_key=' . $search_key . '&offset=' . $offset;
            $url = config('general.node_micro_service') . "user/boxes?$queryString";
            \Log::info("---load boxes node url --->{$url}");
            $result = $this->client->get($url, ['headers' => ['token' => $request->header('token'), 'host' => $_SERVER['HTTP_HOST'], 'user-agent' => config("general.internal_service_id"), 'lang' => $this->locale]]);
            if ($result->getStatusCode() == 200) {
                $response = json_decode($result->getBody()->getContents(), true);
            }
            return $response;
        } catch (\Exception $ex) {
            $log = [];
            $log["function"] = "GuzzleHelper - searchUserBoxes";
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            $log["message"] = $ex->getMessage();
            \Log::info(print_r($log, true));
            return ['success' => false, 'error' => 'generalError'];
        }
    }

    /**
     * Get post by box ID NodeJs
     * @param type $request
     * @param type $inputs
     * @param type $status
     * @return type
     */
    public function getBoxPosts($request, $inputs, $status) {
        try {
            $response = [];
            $queryString = "box_id=" . $inputs['box_id'] . '&offset=' . $inputs['offset'] . '&limit=' . $inputs['limit'] . '&less_than=' . $inputs['less_than'] . '&status=' . $status;
            $url = config('general.node_micro_service') . "box/posts?$queryString";
            $result = $this->client->get($url, ['headers' => ['token' => $request->header('token'), 'host' => $_SERVER['HTTP_HOST'], 'user-agent' => config("general.internal_service_id"), 'lang' => $this->locale]]);
            if ($result->getStatusCode() == 200) {
                $response = json_decode($result->getBody()->getContents(), true)['data'];
                if (isset($response['posts'])) {
                    $response = $response['posts'];
                }
            }
            return $this->getCommonPosts()->prepareIsFayvedPosts($response, $inputs);
        } catch (\Exception $ex) {
            return ['success' => false, 'error' => "generalError"];
        }
    }

    /**
     * Get use posts from NodeJs
     * @param type $request
     * @param type $user_id
     * @param type $inputs
     * @param type $status
     * @return type
     */
    public function getUserPosts($request, $user_id, $inputs, $arr) {
        try {
            $response = [];
            $queryString = "user_id={$user_id}&less_than={$inputs['less_than']}&status={$arr[0]}";
            $url = config('general.node_micro_service') . "user/posts?{$queryString}";
            if ($arr[2]) {
                $url = config('general.profiler_micro_service') . "api/internal/profile/user/posts?{$queryString}";
            }
            $result = (new Client())->get($url, ['headers' => ['login-id' => $arr[1], 'token' => $request->header('token'), 'host' => $_SERVER['HTTP_HOST'], 'user-agent' => config("general.internal_service_id"), 'lang' => $this->locale]]);
            if ($result->getStatusCode() == 200) {
                $response = json_decode($result->getBody()->getContents(), true)['data'];
            }
            return $this->getCommonPosts()->prepareIsFayvedPosts($response, $inputs);
        } catch (\Exception $ex) {
            return ['success' => false, 'error' => trans('messages.generalError')];
        }
    }

    /**
     * Get location By IP
     * @param type $ip
     * @return type
     */
    public function getIpLocation($ip) {
        try {
            $response = [];
            if ($data = $this->getCacheIpInfo($ip)) {
                \Log::info("IP info founded from cache ---" . $ip);
                \Log::info(print_r($data, true));
                $response = $data;
            } else {
                $header = [
                    'headers' => [
                        "ip-address" => $ip,
                        "user-agent" => config("general.internal_service_id")
                    ]
                ];
                $url = config("general.node_mvc_micro_service") . "ip/info";
                $result = $this->client->get($url, $header);
                if ($result->getStatusCode() == 200) {
                    $response = json_decode($result->getBody()->getContents(), true);
                    $this->cacheIpInfo($ip, $response, 8); //Cache for 24H
                }
            }
            return $response;
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return [];
        }
    }

    /**
     * 
     * @param type $source_type
     * @param type $source_id
     * @param type $type
     * @param type $ip
     * @return type
     */
    public function getApiPostRelatedData($post_id = 0, $source_type, $source_id, $item_type_number = 0, $ip = null) {
        try {
            $response = [];
            $log = [];
            $log["message"] = "Post Detail Internal Call-->";
            $log["post_id"] = $post_id;
            $log["source_type"] = $source_type;
            $log["source_id"] = $source_id;
            $log["item_type"] = config("general.item_type_by_number.$item_type_number");
            $log["item_type_number"] = $item_type_number;
            $log["ip"] = $ip;

            if ($source_type != "web") {
//                $source_type = config("general.related_source_types.$source_type");
                $type = config("general.item_type_by_number.$item_type_number");
                $redis_key = config("general.redis_keys.shared_api_related_content") . $source_type . "-" . $type . "-" . $source_id . "-" . app('translator')->getLocale();
                $log['redis_key'] = $redis_key;
                if ($data = $this->getCacheArrayByKey($redis_key)) {
                    \Log::info(print_r($log, true));
                    $response = $data;
                } else {
                    $header = [
                        'headers' => [
                            "fv-agent" => "fayvo_trending",
                            "remote-ip" => $ip,
                            "server-key" => config("general.x_api_key")
                        ]
                    ];

                    $url = config("general.node_mvc_micro_service") . "trending/preview?source_type=$source_type&source_id=$source_id&item_type_number=$item_type_number";

                    $log["auth_url"] = $url;

                    $result = $this->client->get($url, $header);

                    if ($result->getStatusCode() == 200) {
                        $data = json_decode($result->getBody()->getContents(), true);

                        if (!isset($data["message"])) {
                            $response = $data["data"];
                            $this->cacheArrayInKey($redis_key, $response, 24); // For 3 days
                        }
                    }
                }
            } else {
                \Log::info(print_r($log, true));
            }
            return $response;
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = "getApiPostRelatedData";
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();

            \Log::info(print_r($log, true));
            return [];
        }
    }

}
