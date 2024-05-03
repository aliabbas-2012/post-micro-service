<?php

namespace App\Jobs;

class CampaignPostOpen extends Job {

    public $campaign_id = 0;
    public $post_id = 0;
    public $user_uid = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($camp_id, $post_id, $user_uid) {
        $this->campaign_id = $camp_id;
        $this->post_id = $post_id;
        $this->user_uid = $user_uid;
    }

}
