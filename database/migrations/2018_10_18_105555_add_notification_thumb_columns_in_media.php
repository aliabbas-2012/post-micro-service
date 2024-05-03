<?php
class AddNotificationThumbColumnsInMedia extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('post_media', 'notification_file_width') && !Schema::hasColumn('post_media', 'notification_file_height')) {
            Schema::table('post_media', function($table) {
                $table->integer('notification_file_width')->after('home_file_height')->default(0);
                $table->integer('notification_file_height')->after('notification_file_width')->default(0);
            });
        }
       
    }

    public function down() {
        if (Schema::hasColumn('post_media', 'notification_file_width') && Schema::hasColumn('post_media', 'notification_file_height')) {
            Schema::table('post_media', function($table) {
                $table->dropColumn('notification_file_width');
                $table->dropColumn('notification_file_height');
            });
        }
        
    }

}
