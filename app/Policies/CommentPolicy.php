<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PostComment;

class CommentPolicy {

    /**
     * Intercept checks
     *
     * @param User $currentUser
     * @return bool
     */
    public function before() {
        return true;
    }

    /**
     * Determine if a given user can delete
     *
     * @param User $currentUser
     * @param User $user
     * @return bool
     */
    public function archive($currentUser, $comment) {
        echo "<pre>";
        print_r($comment->toArray());
        exit;
        return $currentUser->id === $comment->user_id;
    }

}
