<?php
class AddCollageInMedia extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('post_media', 'collage_file_width') && !Schema::hasColumn('post_media', 'collage_file_height')) {
            Schema::table('post_media', function($table) {
                $table->integer('collage_file_width')->after('thumb_file_height')->default(0);
                $table->integer('collage_file_height')->after('collage_file_width')->default(0);
            });
        }
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::hasColumn('post_media', 'collage_file_width') && Schema::hasColumn('post_media', 'collage_file_height')) {
            Schema::table('post_media', function($table) {
                $table->dropColumn('collage_file_width');
                $table->dropColumn('collage_file_height');
            });
        }
       
    }

}
