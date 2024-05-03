<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Jobs;

use App\Models\View;
use App\Models\SearchKeyword;

/**
 * Description of SaveViewJob
 *
 * @author rizwan
 */
class SaveViewJob extends Job {

    public $connection = "sqs_archive_user";

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $data = [];
    private $is_device = false;
    private $search_key = "";

    public function __construct($data = [], $searchKey = "", $mod = false) {
        $this->data = $data;
        $this->is_device = $mod;
        $this->search_key = $searchKey;
    }

}
