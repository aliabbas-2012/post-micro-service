<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateBucketColumn extends Command {

    /**
     * The name and signature of the console command.
     *
     * php artisan command:update_db_bucket_column
     * 
     * @var string
     */
    protected $signature = 'command:update_db_bucket_column';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update empty bucket column for user and media post';
    private $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->updateColumns();
    }

    private function updateColumns() {
        $isUpdated = \App\Models\User::where('bucket', '<>', 'fayvo-sample')->update(['bucket' => 'fayvo_live']);
        $isMedia = \App\Models\PostMedia::where('bucket', '<>', 'fayvo-sample')->update(['bucket' => 'fayvo_live']);
        echo "Bucket name updaed successfully";
        die;
    }

}
