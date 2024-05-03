<?php

namespace App\Jobs;

class SyncCurrentLocation extends Job {


    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $object_id = null;
    protected $ip = null;
    protected $isUser = false;
    protected $service = 'D';
    protected $inputs = [];

    public function __construct($object_id, $inputs = [], $ip, $service = 'D', $isUser = false) {
        $this->object_id = $object_id;
        $this->ip = $ip;
        $this->service = $service;
        $this->isUser = $isUser;
        $this->inputs = $inputs;
    }

}
