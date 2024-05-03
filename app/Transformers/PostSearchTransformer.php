<?php

namespace App\Transformers;

class PostSearchTransformer extends PostTransformer
{

    public function transform($post)
    {
        return $this->prepareSimplePostResponse($post);
    }
}
