<?php
class AddHomeDimensionsInMedia extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('post_media', 'home_file_width') && !Schema::hasColumn('post_media', 'home_file_height')) {
            Schema::table('post_media', function($table) {
                $table->integer('home_file_width')->after('thumb_file_height')->default(0);
                $table->integer('home_file_height')->after('home_file_width')->default(0);
            });
        }
        
    }

    public function down() {
        if (Schema::hasColumn('post_media', 'home_file_width') && Schema::hasColumn('post_media', 'home_file_height')) {
            Schema::table('post_media', function($table) {
                $table->dropColumn('home_file_width');
                $table->dropColumn('home_file_height');
            });
        }
       
    }

}
