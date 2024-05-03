<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;

/**
 * Description of CommonTrait
 *
 * @author Ali Abbas
 */
trait CommonTrait {

    /**
     * Get user token from headers
     * @param Request $request
     * @return type
     */
    public function getCurrentUser($request) {
        try {
            $token = $request->header('token');
            if (empty($token)) {
                throw new Exception('Token information missing.', 400);
            }

            return $this->nextStepLoginValidation($token);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage(), 400);
        }
    }

    private function nextStepLoginValidation($token) {
        $split_token = $token;
        if (strlen($token) > 50) {

            $split_token = $this->initFayvo()->decodeToken($token);

            $fbId = (isset($split_token['identities'])) ? $split_token['identities'][0]['userId'] : null;

            //when every token is empty!!!
            if ((!isset($split_token['custom:uid']) || empty($split_token['custom:uid'])) && empty($fbId)) {
                throw new Exception('Invalid cognito token', 400);
            }
        }
        return $this->lastStepLoginValidation($split_token, $token);
    }

    public function simplifyToken($token) {
        $tokenInfo = [];


        if (isset($token['custom:uid']) && !empty($token['custom:uid'])) {
            $tokenInfo = ['key' => 'uid', 'value' => $token['custom:uid'], 'is_fb' => false];
        } else if (isset($token['identities'])) {
            $tokenInfo = ['key' => 'facebook_id', 'value' => $token['identities'][0]['userId'], 'is_fb' => true];
        } else {
            $tokenInfo = ['key' => 'uid', 'value' => $token, 'is_fb' => false];
        }

        return $tokenInfo;
    }

    private function lastStepLoginValidation($split_token, $token = "") {

        $tok = $this->simplifyToken($split_token);

        $crit = [
            $tok['key'] => $tok['value']
        ];
        $key = config('general.mem_cache_keys.login') . $tok['value'];
        $user = $this->getCurrentUserCache($key, $crit);
        if (empty($user)) {
            throw new Exception('User not found', 400);
        }

        return $user;
    }

    public function getCurrentUserForRequest($request) {
        try {
            return $this->getCurrentUser($request);
        } catch (\Exception $ex) {

            return null;
        }
    }

    /**
     * 
     * @param type $key
     * @param type $crit
     * @return type
     */
    public function getCurrentUserCache($key, $crit) {
        /**
         * HOT FIX
         */
        return $obj = User::getUserForAppendingInCache($crit);
//        return \Cache::get($key, function () use($crit, $key) {
//                    $obj = User::getUserForAppendingInCache($crit);
//                    \Cache::put($key, $obj, 300);
//
//                    return $obj;
//                });
    }

    /**
     * decrypt encrypted data
     * @param type $data
     * @return type
     */
    public function decryptData($data) {
        return Crypt::decrypt($data);
    }

    /**
     * initialize AWS Cognito client
     * @return \pmill\AwsCognito\CognitoClient
     */
    public function initFayvo() {
        $aws = new \Aws\Sdk(config('cognito'));
        $cognitoClient = $aws->createCognitoIdentityProvider();
        $obj = new \App\Helpers\FayvoCognito($cognitoClient);
        $obj->setAppClientId(config('cognito.app_client_id'));
        $obj->setAppClientSecret(config('cognito.app_client_secret'));
        $obj->setRegion(config('cognito.region'));
        $obj->setUserPoolId(config('cognito.user_pool_id'));
        return $obj;
    }

    /**
     * initiate repository
     * @param type $class
     * @param type $model
     * @return \App\Traits\class
     */
    public function initRepository($class, $model) {
        return new $class(new $model);
    }

    /**
     * validate user with uuid
     * @param type $uid
     * @param type $columns
     * @return type
     * @throws Exception
     */
    public function validateOtherUser($uid, $columns = []) {
        try {
            $user = User::getUserByToken($uid, $columns);
            if (empty($user)) {
                throw new Exception('No user found', 400);
            }
            return $user;
        } catch (\Exception $ex) {
            throw new Exception($ex->getMessage(), 400);
        }
    }

    /**
     * prepare request params
     * @param type $inputs
     * @param type $allowed
     * @return type
     */
    public function prepareRequestInputs($inputs, $allowed = []) {
        if (!empty($allowed)) {
            $inputs = $this->processRequestInputs($inputs, $allowed);
        }
        return $inputs;
    }

    private function processRequestInputs($inputs, $allowed = []) {
        $index = [];
        foreach ($allowed as $key => $value) {
            if (!isset($inputs[$value])) {
                $index[$value] = $this->getParamDefaultValue($value);
            }
        }
        return array_merge($inputs, $index);
    }

    /**
     * Get request param default value
     * @param type $type
     * @return string
     */
    private function getParamDefaultValue($type) {
        $value = "";
        $type = strtolower($type);
        switch ($type) {
            case 'offset':
                $value = 0;
                break;
            case 'limit':
                $value = 40;
                break;
            case 'search_key':
                $value = "";
                break;
            default:
                break;
        }
        return $value;
    }

    /**
     * Trim/filter string
     * @param type $search
     * @return type
     */
    public function trim_string($search = "") {
        if (!empty(trim($search))) {
            if (preg_match('~\p{Arabic}~u', $search)) {
                $search = preg_replace('/[^\x{0600}-\x{06FF}_.A-Za-z0-9 !@#$%^&*()]/u', '', $search);
            } else {
                $search = preg_replace('/[^A-Za-z0-9\._ -]/', '', strtolower(trim($search)));
            }
        }
        return $search;
    }

    /**
     * get lat long from IP address
     */
    public function getLocationFromIP(Request $request, $user) {
        $ip = $request->ip();
        if (\Cache::has('user_location_' . $user->id)) {
            return \Cache::get('user_location_' . $user->id);
        }
        if ($ip == "127.0.0.1") {
            return config("general.default_location");
        }
        $location_resp = file_get_contents(config('general.ipstack') . $ip . "?access_key=" . config('general.api_key'));
        $location_arr = json_decode($location_resp, true);

        if ($location = array_filter([$location_arr["longitude"], $location_arr["latitude"]])) {
            \Cache::put('user_location_' . $user->id, $location, 10);
            return $location;
        }
    }

    /**
     * remove index from array
     * @param type $array
     * @param type $index
     * @return array
     */
    public function removeIndex($array = [], $index = 'posts') {
        $response = [];
        if (!$array->isEmpty()) {
            foreach ($array->toArray() as $key => $value) {
                unset($value[$index]);
                $response[] = $value;
            }
        }
        return $response;
    }

    /**
     * Box privacy validations
     * @param type $boxes
     * @param type $is_following
     * @param type $postUser
     * @return boolean
     */
    public function boxPrivacyValidation($boxes, $is_following, $postUser) {
        $response = false;

        foreach ($boxes as $box) {
           
            if ($is_following && ($box->status == "F" || $box->status == "A")) {
                $response = true;
                break;
            } else if ($postUser["is_live"] && $box->status == "A") {
                $response = true;
                break;
            }
        }
              
        return $response;
    }

    /**
     * Prepare command Es index query
     * @param type $index
     * @param type $id
     * @return type
     */
    public function prepareCommandEsIndex($index, $id, $prefix = 'u') {
        return [
            'index' => [
                '_index' => $index,
                '_type' => 'doc',
                "_id" => "$prefix-$id",
                "routing" => 1
            ]
        ];
    }

    public function getEsClient($host) {
        return ClientBuilder::create()->setHosts([$host])->build();
    }

    /**
     * Get Http Guzzle client
     * @return Client
     */
    public function getGuzzleClient() {
        $headers = ['headers' => [
                'Content-Type' => 'application/json'
            ]
        ];
        $client = new Client();
        return $client;
    }

    /**
     * Get max elastisearch id
     * @param type $client
     * @param type $index
     * @param type $type
     * @return int
     */
    public function getMaxEsId($client, $index, $type = 'user') {
        $params = [
            "index" => $index,
            "type" => "doc",
            "body" => [
                "size" => 0,
                "_source" => false,
                "query" => [
                    "term" => [
                        "type" => $type
                    ]
                ],
                "aggs" => [
                    "max_id" => ["max" => ["field" => "id"]]
                ]
            ]
        ];

        $data = $client->search($params);

        if (!empty($data['aggregations']['max_id']["value"])) {
            return $data['aggregations']['max_id']['value'];
        }
        return 0;
    }

    /**
     * 
     * @param type $index
     * @param type $type
     * @param type $body
     * @return type
     */
    public function prepareEsSearchIndex($index, $type, $body = []) {
        return [
            'index' => $index,
            'type' => $type,
            "body" => $body
        ];
    }

    /**
     * is needle or object exists in array
     * @param type $arr
     * @param type $needle
     * @return boolean
     */
    public function isInArray($arr, $needle) {
        if (in_array($needle, $arr)) {
            return true;
        }
        return false;
    }

    /**
     * Log exception on server console
     */
    public function logException($ex, $method = "undefined", $inputs = []) {
        $message["Method"] = $method;
        $message["Message"] = $ex->getMessage();
        $message["File"] = $ex->getFile();
        $message["Line"] = $ex->getLine();
        \Log::info("=== An exception catched successfully ===");
        \Log::info($message);
    }

    /**
     * verifySDK method 
     * verifies request agent
     * @param type $user_agent
     * return boolean
     */
    public function verifySDKIdentity($user_agent = null) {

        $gateway_ids = $this->prepareAmazonAPIGatewayIds(explode("_", env("AWS_GATEWAY_LIST")));
        \Log::info("--- gatewaay IDs ---");
        \Log::info(print_r($gateway_ids, true));
        $sdk = explode('/', $user_agent);
        \Log::info("--- received SDK ---");
        \Log::info(print_r($sdk, true));
        $api_gateway_sdks = explode(" ", env('ANDROID_SDK') . " " . env('IOS_SDK'));
        \Log::info("--- gatewaay sdks ---");
        \Log::info(print_r($api_gateway_sdks, true));
        if (!in_array($sdk[0], $api_gateway_sdks)) {
            \Log::info(" ---- verifySDKIdentity if ----");
            return false;
        } else {
            \Log::info(" ---- verifySDKIdentity else ----");
            $requested_id = explode(" ", end($sdk));
            $request_gateway_id = end($requested_id);
            \Log::info("--- requested gateway ID ---");
            \Log::info(print_r($request_gateway_id, true));
            if (in_array($request_gateway_id, $gateway_ids)) {
                \Log::info(" ---- verifySDKIdentity verified ----");
                return true;
            } else {
                \Log::info(" ---- verifySDKIdentity not verified ----");
                return false;
            }
        }
    }

    /**
     * 
     * @param type $ids
     * @return array
     */
    public function prepareAmazonAPIGatewayIds($ids) {

        $gateway_ids = [];
        foreach ($ids as $id) {
            $gateway_ids[] = "AmazonAPIGateway_" . $id;
        }
        return $gateway_ids;
    }

    /**
     * Get ip address
     * @return string
     */
    public function getClientIp() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    /**
     * Shift array to keypair array
     * @param type $array
     * @param type $col
     * @return type
     */
    public function shiftArrayToKeyPair($array = [], $col = "id", $isPrint = false) {
        $response = [];
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $response[$val[$col]] = $val;
            } else {
                $response[$val] = $val;
            }
        }
        return $response;
    }

    public function filterPostIdsByType($posts = []) {
        $response = ['media' => [], 'api' => []];
        foreach ($posts as $key => $post) {
            $indexKey = $post['post_type'] == config("general.post_type_index.search") ? 'api' : 'media';
            $response[$indexKey][] = $key;
        }
        return $response;
    }

    /**
     * Not In Use
     */
//    public function getIpLocation($ip) {
//        $response = ["lat" => 0.0, "lon" => 0.0];
//        $key = config("general.mem_cache_keys.ip-address-info") . md5($ip);
//        if ($location = \Cache::get($key)) {
//            return $location;
//        } else {
//            $headers = ['headers' => [
//                    'user-agent' => config("general.internal_service_id"),
//                    'ip-address' => $ip
//                ]
//            ];
//            $url = config("general.node_micro_service") . "ip/info";
//            $result = $this->getGuzzleClient()->get($url, $headers);
//            if ($result->getStatusCode() == 200) {
//                $result = json_decode($result->getBody()->getContents(), true);
//                $response["lat"] = $result["lat"];
//                $response["lon"] = $result["lng"];
//                \Cache::put($key, $response, 10);
//            }
//        }
//        return $response;
//    }
}
