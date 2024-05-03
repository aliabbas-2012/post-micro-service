<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Helpers;

/**
 * Description of BaseProcess
 *
 * @author rizwan
 */
class BaseProcess {

    public function __construct() {
        
    }

    /**
     * prepare user device tpkens (ios+android)
     * @param type $tokens
     * @return type
     */
    public function prepareTokens($tokens) {
        $response = ["ios" => [], 'android' => []];
        foreach ($tokens as $key => $token) {
            if (empty($token['device_token'])) {
                continue;
            }
            $response = $this->tokenArr($response, $token);
        }
        return $response;
    }

    /**
     * Prepare token array
     * @param type $response
     * @param type $token
     * @return type
     */
    public function tokenArr($response, $token) {
        if ($token['device_type'] == 'android') {
            $response['android'][] = $token['device_token'];
        } else {
            $response['ios'][] = $token['device_token'];
        }
        return $response;
    }

    /**
     * prepare push notification user array
     * @param type $user
     * @return type
     */
    public function prepareUser($user = []) {
        $response = [];
        if (!empty($user)) {
            $response['id'] = $user['uid'];
            $response['username'] = $user['username'];
            $response['picture'] = $user['thumb'];
        }
        return $response;
    }

}
