<?php

namespace App\Transformers;

/**
 * Response handling for BoxPosts
 * @author ali
 */
class UserPostsTransformer extends PostTransformer {

    public function transform($userPost) {
        if (empty($userPost)) {
            return [];
        }
        return $this->prepareSimplePostResponse($userPost->post);
    }

}
