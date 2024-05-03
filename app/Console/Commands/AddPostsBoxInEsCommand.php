<?php

namespace App\Console\Commands;

use App\Models\PostBox;
use Carbon\Carbon;
use Elasticsearch\ClientBuilder;

class AddPostsBoxInEsCommand extends BaseCommand {

    //
    /**
     * Add All Posts
     *
     * @var string
     */
    protected $signature = 'add_box_posts:es';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $client;

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
        $this->client = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
        $this->performLogic();
    }

    private function performLogic() {
        \DB::enableQueryLog();
        $user_id = 24504;
        $raw = "Select count(*) as total from user_posts_boxes where archive = false ";
//        $raw.= " and user_id = $user_id";
        $data = \DB::select($raw);
        $max_id = 0;
        $limit = 10000;

        $total_pages = ceil($data[0]->total / $limit);
        $this->output->progressStart($total_pages);
        for ($i = 0; $i < $total_pages; $i++) {
            $sql = PostBox::select("id", "created_at", "user_id", "user_posts_id", "box_id")
                    ->with(["box" => function($sql) {
                            $sql->select("id", "name", "status");
                        }])
                    ->whereHas("box", function($sql) {
                        $sql->where("archive", "=", false);
                    })
                    ->where("archive", "=", false)
                    ->where("id", ">", $max_id)
                    ->orderBy("id", "ASC")
                    ->limit($limit);

            $posts = $sql->get();
            $res = $this->updateES($posts);

            $max_id = $posts->max('id');

            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

    /**
     * @param type $posts
     * @return type
     */
    private function updateES($posts) {
        $params = [];
        foreach ($posts as $post) {

            $params['body'][] = [
                'index' => [
                    '_index' => $this->esIndex,
                    '_type' => 'doc',
                    "_id" => "pbx-" . $post->id,
                    "routing" => 1
                ]
            ];

            $post_data = [
                "id" => $post->id,
                "db_id" => $post->id,
                "name" => $post->box->name,
                "status" => $post->box->status,
                "user_id" => "u-" . $post->user_id,
                "object_id" => $post->box_id,
                "type" => ["name" => "post_box", "parent" => "p-" . $post->user_posts_id],
                "score" => $post->score,
                "text_content" => $post->text_content,
                "created_at" => Carbon::parse($post->created_at)->format("Y-m-d\TH:i:s\Z"),
            ];
            $params['body'][] = $post_data;
        }

        if (!empty($params)) {
//            print_r($params);
            $responses = $this->client->bulk($params);
//            print_r($responses);
            return $responses;
        }
    }

}
