<?php

class UpdateArchiveRecordsInPostBox extends BaseMigration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (Schema::hasColumn('user_posts_boxes', 'status')) {

            $query = "UPDATE user_posts_boxes Set 
                    user_posts_boxes.status = 'D',user_posts_boxes.archive = 1
                    where (
                                Select count(*) from user_posts 
                                where user_posts.id = user_posts_boxes.user_posts_id 
                                AND (user_posts.archive = 1 or user_posts.status ='D') 
                            )>0 ";
            \DB::statement($query);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        return true;
    }

}
