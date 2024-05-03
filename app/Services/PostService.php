<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use App\Models\PostBox;

/**
 * Description of PostService
 *
 * @author rizwan
 */
class PostService extends BaseService {

    public function __construct() {
        
    }

    public function getPost($user, $inputs = []) {
        if ($postBox = PostBox::getNewPostDetail($inputs, $user->id)) {
            $response = $this->processResponse($postBox, $user);
        }
        return [];
    }

    private function processResponse($post, $user) {
        
    }

}
