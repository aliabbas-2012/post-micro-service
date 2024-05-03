<?php


class AddBucketNameInPostMedia extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('post_media', 'bucket')) {
            Schema::table('post_media', function($table) {
                $table->string('bucket')->after('md5_filename')->default("favyo_live");
            });
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::hasColumn('post_media', 'bucket')) {
            Schema::table('post_media', function($table) {
                $table->dropColumn('bucket');
       
            });
        }
        
    }

}
