<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Event;

class DeleteArchivePostAssociations extends Command {

    use \App\Traits\CommonTrait;

    /**
     * The name and signature of the console command.
     * php artisan command:move_archive_post_associatons
     * @var string
     */
    protected $signature = 'command:move_archive_post_associatons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete and Move already archived post associations like post_location/post_media/post_boxes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $client;
    protected $deleteEsPost;
    protected $postIDs = [];

    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        return true;
    }

}
