<?php

namespace App\Repositories\Contracts;

/**
 * Description of CommentRepository
 *
 * @author rizwan
 */
interface BoxBookmarkRepository {

    public function isBookmarked($user_id, $relation_id, $relation_type);
}
