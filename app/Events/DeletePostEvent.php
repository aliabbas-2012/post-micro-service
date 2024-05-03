<?php

namespace App\Events;

/**
 * Description of DeletePostEvent
 *
 * @author ali
 */
class DeletePostEvent extends Event {

    public $model, $box_ids;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($model, $box_ids = []) {
        $this->model = $model;
        $this->box_ids = $box_ids;
        \Log::info("===== Delete Post Event Called ====");
    }

}
