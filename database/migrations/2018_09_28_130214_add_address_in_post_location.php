<?php
class AddAddressInPostLocation extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('post_location', 'address')) {
            Schema::table('post_location', function($table) {
                $table->integer('address')->after('location_name')->default(0);
            });
        }
        
    }

    public function down() {
        if (Schema::hasColumn('post_location', 'address')) {
            Schema::table('post_location', function($table) {
                $table->dropColumn('address');
            });
        }
        
    }

}
