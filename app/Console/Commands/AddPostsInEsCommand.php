<?php

namespace App\Console\Commands;

use App\Models\UserPost;
use Carbon\Carbon;
use Elasticsearch\ClientBuilder;

class AddPostsInEsCommand extends BaseCommand {

    //
    /**
     * Add All Posts
     *
     * @var string
     */
    protected $signature = 'add_posts:es';

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
        $raw = "Select count(*) as total from user_posts where archive = false ";
//        $raw.= " and user_id = $user_id";
        $data = \DB::select($raw);
        $max_id = 0;
        $limit = 1000;

        $total_pages = ceil($data[0]->total / $limit);
        $this->output->progressStart($total_pages);
        for ($i = 0; $i < $total_pages; $i++) {
            $sql = UserPost::select("id", "text_content", "user_id", "score", "created_at", "post_type_id", "client_ip_longitude", "client_ip_latitude")
                    ->where("archive", "=", false)
//                    ->where("user_id", "=", $user_id)
                    ->where("id", ">", $max_id)
                    ->orderBy("id", "ASC")
                    ->limit($limit);
            $sql->with(["postMedia", "place"]);
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
                    "_id" => "p-" . $post->id,
                    "routing" => 1
                ]
            ];
            $post_data = [
                "id" => $post->id,
                "post_type_id" => $post->post_type_id,
                "user_id" => "u-" . $post->user_id,
                "type" => ["name" => "post", "parent" => "u-" . $post->user_id],
                "score" => $post->score,
                "name" => $post->text_content,
                "created_at" => Carbon::parse($post->created_at)->format("Y-m-d\TH:i:s\Z"),
            ];
            if (!empty($post->client_ip_longitude)) {
                $post_data["created_location"] = [
                    "lat" => (float) $post->client_ip_latitude,
                    "lon" => (float) $post->client_ip_longitude,
                ];
            }

            if (!empty($post->place)) {
                $post_data["place"] = [
                    "fs_location_id" => $post->place->fs_location_id,
                    "location_name" => $post->place->location_name,
                    "address" => $post->place->address,
                ];
                $post_data["post_location"] = [
                    "lat" => (float) $post->place->latitude,
                    "lon" => (float) $post->place->longitude,
                ];
            }


            $post_data['post_media'] = $post->postMedia->toArray();
            $params["body"][] = $post_data;
        }
        if (!empty($params)) {

            $responses = $this->client->bulk($params);

            return $responses;
        }
    }

}
