<?php

namespace App\Traits;

use GuzzleHttp\Client;

/**
 * Description of CommonTrait
 *
 * @author rizwan
 */
trait BoxTrait {

    /**
     * prepare box posts
     * @param type $request
     * @param type $current_user
     * @return type
     */
    public function prepareBoxPostsInputs($request, $current_user) {
        $inputs = $request->all();
        $data['user_id'] = $current_user->id;
        $data['box_id'] = (isset($inputs['box_id']) && $inputs['box_id'] > 0) ? $inputs['box_id'] : 0;
        $data['limit'] = (isset($inputs['limit']) && $inputs['limit'] > 0) ? $inputs['limit'] : 40;
        $data['offset'] = (isset($inputs['offset']) && $inputs['offset'] > 0) ? $inputs['offset'] : 0;
        $data['less_than'] = (isset($inputs['less_than']) && $inputs['less_than'] > 0) ? $inputs['less_than'] : 0;
        $data['fsq'] = (isset($inputs['fsq']) && !empty($inputs['fsq'])) ? $inputs['fsq'] : "";
        return $data;
    }

    /**
     * Check self user
     * @param type $inputs
     * @param type $current_user
     * @return boolean
     */
    public function isOhterUser($inputs, $current_user) {
        $response = false;
        if (isset($inputs['profile_id']) && $current_user->uid != $inputs['profile_id']) {
            $response = true;
        }
        return $response;
    }

    /**
     * Is Other user is valid
     * @param type $inputs
     * @param type $columns
     * @return boolean
     */
    public function isOTherUserValid($inputs, $columns) {
        try {
            $user = \App\Models\User::getUserByToken($inputs['profile_id'], $columns);
            if (($user->blocked_status || $user->blocked_me_status) || ($user->follow_status != "A" && $user->is_private)) {
                return false;
            }
            return $user;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Remove Box from cache
     * @param type $box_id
     * @param type $token
     * @return boolean
     */
    public function removeEsBox($box_id, $token) {
        try {
            $url = config("general.user_micro_service") . "archiveEsBox";
            $headers = ['token' => $token, "auth-key" => config("general.auth_key"), 'user-agent' => config("general.internal_service_id")];
            \Log::info("------url---------");
            \Log::info($url);

            $newClient = new Client();
            $request = $newClient->delete($url, [
                'headers' => $headers,
                "query" => [
                    "id" => $box_id
                ]
            ]);


            \Log::info($request->getStatusCode());
            return $request->getStatusCode() == 200 ? true : false;
        } catch (\Exception $ex) {
            return false;
        }
    }

}
