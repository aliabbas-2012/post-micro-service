<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserPost as Post;
use App\Models\PostMedia;
class TestEloquentCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test_eloqent:orm';

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
        \DB::enableQueryLog();
        $post = new Post(["text_content" => "test", "user_id" => 5, "post_type_id" => 5]);
      
        $post->save();
          $items = [
            ['file' => 'test1.jpg','user_post_id'=>$post->id],
            ['file' => 'test2.jpg','user_post_id'=>$post->id],
        ];
        PostMedia::insert($items);

        print_r($post->toArray());
        dd(\DB::getQueryLog());
    }

}
