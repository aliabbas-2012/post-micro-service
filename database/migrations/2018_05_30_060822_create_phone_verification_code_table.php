<?php

class CreatePhoneVerificationCodeTable extends BaseMigration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('phone_verification_code')) {
            Schema::create('phone_verification_code', function ($table) {
                $table->increments('id');
                $table->string('phone');
                $table->integer('code');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('phone_verification_code');
    }

}
