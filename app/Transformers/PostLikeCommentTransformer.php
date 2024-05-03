<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Transformers;

use App\Transformers\BaseTransformer;
use App\Models\PostComment;
use Carbon\Carbon;

/**
 * Description of PostCommentTransformer
 *
 * @author rizwan
 */
class PostLikeCommentTransformer extends PostCommentTransformer {

    public function transform($data = []) {
        $response = ['comments' => []];
        $response['likes'] = !empty($data['likes']) ? $data['likes'] : [];
        foreach ($data['comments'] as $comment) {
            $response['comments'][] = $this->prepareCommentResponse($comment);
        }
        return $response;
    }

}
