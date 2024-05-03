<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Events;

/**
 * Description of FollowEvent
 *
 * @author farazirfan
 */
class ActivityEvent extends Event {

    public $model;
    
    public $type;
    public $activityUser;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($model, $type) {
        $this->model = isset($model['model']) ? $model['model'] : $model;
        $this->activityUser = isset($model['user']) ? $model['user'] : null;
        $this->type = $type;
    }

}
