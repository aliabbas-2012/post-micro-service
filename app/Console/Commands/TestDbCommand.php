<?php

namespace App\Console\Commands;

class TestDbCommand extends BaseCommand {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test_db_:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $sql = "Select count(*) from users";
        $res = \DB::select($sql);
        print_r($res);
    }

}
