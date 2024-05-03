<?php

//app/Http/Controllers/Controller.php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Exception;

class Controller extends BaseController {

    use ResponseTrait;
    use \App\Traits\CacheTrait;
    use \App\Traits\MessageTrait;
    use \App\Traits\RedisTrait;

    /**
     * Constructor
     *
     * @param Manager|null $fractal
     */
    public function __construct(Manager $fractal = null) {
        $fractal = $fractal === null ? new Manager() : $fractal;
        $this->setFractal($fractal);
    }

    /**
     * Validate HTTP request against the rules
     *
     * @param Request $request
     * @param array $rules
     * @return bool|array
     */
    protected function validateRequest(Request $request, array $rules) {
        // Perform Validation
        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->messages();

            // crete error message by using key and value
            foreach ($errorMessages as $key => $value) {
                $errorMessages[$key] = $value[0];
            }

            return $errorMessages;
        }

        return true;
    }

    /**
     * prepare user login credentials
     * @param type $inputs
     * @return int
     */
    public function prepareLoginInputs($inputs) {
        $response = [];
        if (!filter_var($inputs['email'], FILTER_VALIDATE_EMAIL)) {
            $response['username'] = strtolower($inputs['email']);
        } else {
            $response['email'] = $inputs['email'];
        }
        $response['role_id'] = 2;
        $response['archive'] = 0;
        return $response;
    }

    /**
     * validate post
     * @param type $post
     * @param type $param_type  
     *      s or o 
     * @return boolean
     * @throws ValidationException
     */
    public function isValidPost($post, $param_type = "s") {
        if ($param_type == "s") {
            $post = \App\Models\UserPost::getPostById($post);
        }
        $this->verifyPostObject($post);
        /**
         * 
         */
        return $post;
    }

    /**
     * Following method is a helper method will be called by class itself to validate post object
     * @param \App\Models\UserPost $post
     * @throws Exception
     */
    private function verifyPostObject($post) {

        if (!$post instanceof \App\Models\UserPost || ($post->archive == 1 || $post->archive == true)) {

            throw new Exception(trans('messages.postNotAvailable'), 400);
        }
    }

    /**
     * prepare user input data
     * @param type $request
     * @return array
     */
    public function getInputData($request) {
        $inputs = isset($request->inputs) && !empty($request->inputs) ?
                $request->inputs :
                [];
        return $inputs;
    }

    public function getGeoLocationInputData($request, $user) {
        $input = $this->getInputData($request);
        if (!empty($input["location"])) {
            $input["location"] = explode(",", $input["location"]);
        } else {
            $input["location"] = $this->getLocationFromIP($request, $user);
        }
        return $input;
    }

    /**
     * Prepare user with follow status
     * @param type $user
     */
    public function prepareUserWithStatus($user) {
        $response['id'] = $user['uid'];
        $response['username'] = $user['username'];
        $response['full_name'] = $user['full_name'];
        $response['is_private'] = !boolval($user['is_live']);
        $response['picture'] = $user['thumb'];
        $response['follow_status'] = !empty($user['is_followed']) ? $user['is_followed']['status'] : "";
        return $response;
    }

    public function getClassClient($class) {
        return new $class;
    }

    /**
     * Save device current location
     * @param type $device
     * @param type $ip
     * @param type $inputs
     * @return boolean
     */
    public function saveUserCurrentLocation($user, $ip, $inputs, $service = 'H', $device_id = "") {

        if (isset($inputs["offset"]) && $inputs["offset"] <= 0) {
            if (!empty($inputs["lat"]) && !empty($inputs["lon"])) {
                $loc = ["lat" => $inputs["lat"], "lon" => $inputs["lon"],
                    "client_ip_address" => $ip, "service" => $service];
                $this->cacheUserCurrentLocation($user->id, $device_id, ["lat" => $loc["lat"], "lon" => $loc["lon"]]);
                return \App\Models\LocationHistory::saveLocation($loc, $user);
            } else if ($queue = env("SQS_NAME")) {
                // Dispatch job here...
                dispatch(new \App\Jobs\SyncCurrentLocation($user->id, [], $ip, $service, true));
            }
        }
        return true;
    }

    /**
     * Track post view
     * @param type $post
     * @param type $user_id
     * @param type $ip
     * @return type
     */
    protected function saveView($arr, $type = "P", $info, $inputs = [], $source_key = "") {
        return true; // Temporarily close due to server ES unavailability and view data not in use
        // Post detail views tracking close after approval of @Nouman Tariq Nov 30, 2021 
        if ($type != "PD") {
            $view = [];
            $view["object_id"] = $arr[1]->id; // UserID
            $view["object_type"] = "U";
            $view["relation_id"] = $arr[0]['id']; //PostID or BoxID or AttrID(API + LOCATION)
            $view["relation_type"] = $type;       // P or B or A
            $view["source_key"] = $source_key;
            $view["ip_address"] = $info[0];
            $view["device_id"] = isset($info[1]) && !empty($info[1]) ? $info[1] : "";
            $view["search_key"] = isset($inputs["search_key"]) ? $inputs["search_key"] : ""; //later added
            //$view["others"] = ['relation_object' => $arr[0], 'object' => $arr[1]]; // commented due to payload size after discussion with @Ali April 07, 2021
            if (in_array(env("APP_ENV"), ['staging', 'production'])) {
                dispatch(new \App\Jobs\SaveViewJob($view, "", false));
            }
        }
        return true;
    }

    /**
     * Validate Is new post detail detail request
     * @param type $agent
     * @return boolean
     */
    public function isNewPost($agent) {
        $isNewPostDetail = false;
        if (config("general.base_app_version") >= 4.3) {
            $isNewPostDetail = true;
        }
        return $isNewPostDetail;
    }

    /**
     * Get Request device
     * @param type $agent
     * @return string
     */
    public function getRequestDeivce($agent) {
        $device = "android";
        $agent = strtolower($agent);
        if (strpos($agent, 'aws-sdk-ios') !== false) {
            $device = "ios";
        }
        return $device;
    }

    /**
     * Validate user agent for same preview call
     * @param type $request
     * @return boolean
     */
    public function isSamePreviewCall($request) {
        $isSamePreviewCall = false;
        $agent = strtolower($request->header('user-agent'));
        if (config("general.base_app_version") >= 4.4) {
            $isSamePreviewCall = true;
        } else if (in_array($agent, config("general.internal_services"))) {
            $isSamePreviewCall = true;
        }
        return $isSamePreviewCall;
    }

    /**
     * Validate comment post
     * @param type $post_id
     * @return type
     * @throws Exception
     */
    public function validateCommentPost($post_id) {
        $post = \App\Models\UserPost::getBaseInfoById($post_id);
        if (boolval($post['archive'])) {
            throw new Exception(trans('messages.postNotAvailable'), 400);
        }
        return $post;
    }

}
