<?php

class AddIndexInPostLocation extends BaseMigration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (Schema::hasColumn('post_location', 'fs_location_id')) {
            Schema::table('post_location', function($table) {

                $table->index('fs_location_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::hasColumn('post_location', 'fs_location_id')) {
            Schema::table('post_type_id', function($table) {
                $table->dropIndex(['fs_location_id']);
            });
        }
    }

}
