<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;
use App\Models\UserPost;
use Event;
use App\Http\EsQueries\DeletePost;

class DeleteArchivedPostsEs extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete_archived_posts:es';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Archived Posts from ES';

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
        $client = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
        $deleted_posts = UserPost::select("id")->where("archive", "=", true)->orderBy("deleted_at", "DESC")->limit(2000);
        $deleteEsPost = new DeletePost();

        $post_ids = [];
        foreach ($deleted_posts->get() as $model) {
            array_push($post_ids,$model->id);
        }
        

        $params_del_box_post = $deleteEsPost->getBulkBoxPostDeleteBody($post_ids);
        $resp1 = $client->updateByQuery($params_del_box_post);
        print_r($resp1);
        
        $params_del = $deleteEsPost->getBulkDeletePostBody($post_ids);
        $resp2 = $client->deleteByQuery($params_del);
        print_r($resp2);
        

    }

}
