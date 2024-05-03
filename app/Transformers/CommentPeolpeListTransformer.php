<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Transformers;

use App\Transformers\BaseTransformer;

/**
 * BoxPostsGroupTransformer
 * @author rizwan
 */
class CommentPeolpeListTransformer extends BaseTransformer {

    public function transform($user) {
        $response = [];
        if (!empty($user)) {
            $user['bucket'] = !empty($user['bucket']) ? $user['bucket'] : "fayvo-sample";
            $response['id'] = $user['uid'];
            $response['username'] = $user['username'];
            $response['picture'] = $this->getUserProfileImage($user['picture'], "thumb", $user['bucket']);
            $response['is_verified'] = isset($user['is_verified']) ? boolval($user['is_verified']) : false;
        }
        return $response;
    }

}
