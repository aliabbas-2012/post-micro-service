<?php

namespace App\Transformers;

/**
 * Response handling for BoxPosts
 * @author ali
 */
class PostTagUserTransformer extends PostTransformer {

    public function transform($user) {

        $response = [];
        if (!empty($user)) {
            $response['id'] = $user['user']['uid'];
            $response['username'] = $user['user']['username'];
            $response['picture'] = $user['user']['thumb'];
            $response['is_private'] = !boolval($user['user']['is_live']);
            $response['is_verified'] = boolval($user['user']['is_verified']);
            $response['follow_status'] = isset($user['user']['is_followed']) && !empty($user['user']['is_followed']) ? $user['user']['is_followed']['status'] : "";
        }
        return $response;
    }

}
