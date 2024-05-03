<?php

class AddUidArchivePostTable extends BaseMigration {

    public function up() {

        if (!Schema::hasColumn('user_posts', 'uid')) {
            Schema::table('user_posts', function($table) {
                $table->string('uid')->after('id');
            });
        }
        if (!Schema::hasColumn('user_posts', 'status')) {
            Schema::table('user_posts', function($table) {
                $table->enum('status',['A','B','D'])->default('A')->after('archive')->comment="A=active,b=blocked,D=deleted";
            });
        }
       
    }

    public function down() {
        if (Schema::hasColumn('user_posts', 'uid')) {
            Schema::table('user_posts', function($table) {
                $table->dropColumn('uid');
                $table->dropColumn('status');
            });
        }
        
    }

}
