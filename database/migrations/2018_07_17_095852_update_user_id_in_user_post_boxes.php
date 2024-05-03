<?php


class UpdateUserIdInUserPostBoxes extends BaseMigration {


    public function up() {

        if (Schema::hasColumn('user_posts_boxes', 'user_id')) {

            $query = "UPDATE user_posts_boxes Set 
                    user_posts_boxes.user_id = (Select user_id from 
                    user_posts where user_posts.id = user_posts_boxes.user_posts_id
                ) where user_posts_boxes.user_id  = 0 ;";
            \DB::statement($query);
        }
    }

    public function down() {
        return true;
    }

}
