<?php

namespace App\Jobs;



class SendPushNotification extends Job {

    private $data = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($object) {
        $this->data = $object;
    }


}
