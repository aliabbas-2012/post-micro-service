<?php
class AddThumbWidthAndHeightInMedia extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('post_media', 'thumb_file_width') && !Schema::hasColumn('post_media', 'thumb_file_height')) {
            Schema::table('post_media', function($table) {
                $table->integer('thumb_file_width')->after('medium_file_height')->default(0);
                $table->integer('thumb_file_height')->after('thumb_file_width')->default(0);
            });
        }
        
    }

    public function down() {
        if (Schema::hasColumn('post_media', 'thumb_file_width') && Schema::hasColumn('post_media', 'thumb_file_height')) {
            Schema::table('post_media', function($table) {
                $table->dropColumn('thumb_file_width');
                $table->dropColumn('thumb_file_height');
            });
        }
       
    }

}
