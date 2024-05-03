<?php

class AddUuidColumnInUsers extends BaseMigration {

    public function up() {
        if (!Schema::hasColumn('users', 'uid')) {
            Schema::table('users', function($table) {
                $table->string('uid')->after('id');
            });
        }
    }

    public function down() {
        if (Schema::hasColumn('users', 'uid')) {
            Schema::table('users', function($table) {
                $table->dropColumn('uid');
            });
        }
    }

}
