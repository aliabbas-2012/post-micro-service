<?php


class AddUserIdInUserPostBoxes extends BaseMigration {


    public function up() {
        if (!Schema::hasColumn('user_posts_boxes', 'user_id')) {
            Schema::table('user_posts_boxes', function($table) {
                $table->integer('user_id')->after('box_id')->default(0);
                $table->index('user_id');
            });
        }
    }


    public function down() {
        if (Schema::hasColumn('user_posts_boxes', 'user_id')) {
            Schema::table('user_posts_boxes', function($table) {
                $table->dropColumn('user_id');
            });
        }
    }

}
