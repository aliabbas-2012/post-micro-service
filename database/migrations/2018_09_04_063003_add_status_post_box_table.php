<?php


class AddStatusPostBoxTable extends BaseMigration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasColumn('user_posts_boxes', 'status')) {
            Schema::table('user_posts_boxes', function($table) {
                $table->enum('status', ['A', 'B', 'D'])->default('A')->after('archive')->comment = "A=active,b=blocked,D=deleted";
                $table->index('archive');
                $table->index('status');
            });
        }
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::hasColumn('user_posts_boxes', 'status')) {
            Schema::table('user_posts_boxes', function($table) {

                $table->dropColumn('status');
            });
        }
        
    }

}
