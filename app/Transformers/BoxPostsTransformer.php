<?php

namespace App\Transformers;

/**
 * Response handling for HomePosts
 * @author ali
 */
class BoxPostsTransformer extends PostTransformer
{

    public function transform($homePost)
    {
        return $this->prepareSimplePostResponse($homePost->post);
    }

}
