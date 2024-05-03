<?php

namespace App\Jobs;

class EngagementPushnotificationJob extends Job {

    public $connection = "sqs_push_notifcation";
    public $data = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($object) {

        \Log::info('<---- notification job in user microservice --->');
        $this->data = $object;
        \Log::info(print_r($object, true));
    }

}
