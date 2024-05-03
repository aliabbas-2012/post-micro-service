<?php

class UpdateLocationInElasticSearch extends BaseMigration {

    public function up() {
        $this->updatePostLocationLatLng();
        $sql = "
           UPDATE user_posts a 
            JOIN users b ON a.user_id = b.id 
            and (b.latitude IS NOT NULL and b.latitude <> '')  
            SET 
            a.client_ip_latitude = b.latitude,
            a.client_ip_longitude = b.longitude
            WHERE a.client_ip_latitude iS NULL or a.client_ip_latitude = '';";

        \DB::statement($sql);
        $this->updatePostDefaultLatLng();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        //
        return true;
    }

    private function updatePostLocationLatLng() {
        $sql = "
            UPDATE user_posts a 
            JOIN post_location b ON a.id = b.user_post_id 
            and (b.latitude IS NOT NULL and b.latitude <> '')  
            SET 
            a.client_ip_latitude = b.latitude,
            a.client_ip_longitude = b.longitude
            WHERE a.client_ip_latitude iS NULL or a.client_ip_latitude = '';


            ";
        \DB::statement($sql);
        return true;
    }

    private function updatePostDefaultLatLng() {
        $sql = "
           UPDATE user_posts a 
            SET 
            a.client_ip_latitude = 23.8859,
            a.client_ip_longitude = 45.0792
            WHERE a.client_ip_latitude iS NULL or a.client_ip_latitude = '';";

        \DB::statement($sql);
        return true;
    }

}
