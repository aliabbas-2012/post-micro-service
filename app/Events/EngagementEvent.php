<?php

namespace App\Events;

class EngagementEvent extends Event {

    public $notification_model;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($notification) {
        $this->notification_model = $notification;
    }

}
