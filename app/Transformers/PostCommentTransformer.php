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
class PostCommentTransformer extends BaseTransformer {

    public function transform($comment) {

        return $this->prepareCommentResponse($comment);
    }

    public function prepareCommentResponse(PostComment $comment) {
        $response = [];
        if (!empty($comment)) {
            $response['id'] = (int) $comment->id;
            $response['comment'] = (string) $comment->comment;
            $response['user_id'] = (string) $comment->user->uid;
            $response['username'] = (string) $comment->user->username;
            $response['picture'] = (string) $comment->user->thumb;
            $response['created_at'] = $this->parseCarbonFormat($comment->created_at);
        }
        return $response;
    }

}
