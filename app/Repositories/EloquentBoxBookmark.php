<?php

namespace App\Repositories;

use App\Repositories\Contracts\BoxBookmarkRepository;
use App\Models\BoxBookmark;

/**
 * Description of EloquentPostComment
 *
 * @author rizwan
 */
class EloquentBoxBookmark extends AbstractEloquentRepository implements BoxBookmarkRepository {

    /**
     * Validate viewed content is bookmarked or not
     * @param type $user_id
     * @param type $relation_id
     * @param type $relation_type
     * @return boolean
     */
    public function isBookmarked($user_id, $relation_id = 0, $relation_type = "B") {
        $query = $this->model->where(function($sql) use($user_id, $relation_id, $relation_type) {
            $sql->where("user_id", "=", $user_id);
            $sql->where("relation_id", "=", $relation_id);
            $sql->where("relation_type", "=", $relation_type);
            $sql->where("status", "=", "A");
        });
        return $query->exists() ? true : false;
    }

}
