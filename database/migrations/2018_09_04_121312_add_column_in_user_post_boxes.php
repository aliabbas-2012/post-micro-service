<?php


class AddColumnInUserPostBoxes extends BaseMigration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasColumn('user_posts_boxes', 'post_type_id')) {
            Schema::table('user_posts_boxes', function($table) {
                $table->integer('post_type_id')->default(1)->after('user_id')->comment = "Duplicate the post_type_id of user_posts";
                $table->index('post_type_id');
            });

             $query = "UPDATE user_posts_boxes Set 
                    user_posts_boxes.post_type_id = (Select post_type_id from 
                    user_posts where user_posts.id = user_posts_boxes.user_posts_id
                )  ;";
            \DB::statement($query);
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::hasColumn('user_posts_boxes', 'post_type_id')) {
            Schema::table('post_type_id', function($table) {
                $table->dropColumn('status');
            });
        }
       
    }

}
