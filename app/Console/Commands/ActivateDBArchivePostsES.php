<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ActivateDBArchivePostsES extends Command {

    use \App\Traits\CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activate_archive_posts:es {user?}';
    protected $client;
    protected $postsNotInEs = [];
    protected $postsInEs = [];

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
        $this->client = $this->getEsClient(config("elastic_search.path"));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $user_id = $this->argument('user');
        if (empty($user_id) || $user_id <= 0) {
            echo "\n\nuser information missing\n\n";
            die;
        }
        return $this->activatePosts($user_id);
    }

    private function activatePosts($user_id) {
        if ($posts = $this->getArchivePosts($user_id)) {
            echo "\n\nDatabase posts\n";
            echo "<pre>";
            print_r($posts);
            if ($esPosts = $this->getPostsFromES(array_unique($posts))) {
                echo "\n\nElasticsearch posts\n";
                echo "<pre>";
                print_r($esPosts);
                foreach ($posts as $key => $value) {
                    if (in_array($value, $esPosts)) {
                        $this->postsInEs[] = $value;
                    } else {
                        $this->postsNotInEs[] = $value;
                    }
                }
            }
        }
        echo "\n\nDB posts in elasticsearch\n";
        echo "<pre>";
        print_r($this->postsInEs);
        $this->activateDBPosts();
        $this->archiveDBPosts();
        echo "\n\n";
        echo count($this->postsInEs) . " posts activated\n";
        die;
    }

    /**
     * Activate database posts
     * @return boolean
     */
    private function activateDBPosts() {
        echo "\n\nDB activated posts\n";
        echo "<pre>";
        print_r($this->postsInEs);
        if (!empty($this->postsInEs)) {
            $isUpdated = \App\Models\UserPost::whereIn('id', $this->postsInEs)
                    ->update(['archive' => false, 'status' => 'A']);
            return $isUpdated;
        }
        return true;
    }

    /**
     * Activate database posts
     * @return boolean
     */
    private function archiveDBPosts() {
        echo "\n\nDB archived posts\n";
        echo "<pre>";
        print_r($this->postsNotInEs);
        if (!empty($this->postsNotInEs)) {
            $isUpdated = \App\Models\UserPost::whereIn('id', $this->postsNotInEs)
                    ->update(['archive' => true, 'status' => 'D']);
            return $isUpdated;
        }
        return true;
    }

    /**
     * Get user archived posts
     * @param type $user_id
     * @return type
     */
    private function getArchivePosts($user_id) {
        $posts = \App\Models\UserPost::where(function($sql) use($user_id) {
                    $sql->where('user_id', '=', $user_id);
                    $sql->where('archive', '=', 1)->where('status', '=', 'A');
                })->get();
        return !$posts->isEmpty() ? array_column($posts->toArray(), 'id') : [];
    }

    private function getPostsFromES($posts = []) {
        $response = [];
        if (!empty($posts)) {
            $query = [
                "index" => "trending",
                "type" => "doc",
                "body" => $this->getPostEsQuery($posts),
            ];
            $result = $this->client->search($query);
            if (!empty($result['aggregations']['posts']['filtered']['top_posts']['buckets'])) {
                $response = array_column($result['aggregations']['posts']['filtered']['top_posts']['buckets'], 'key');
            }
        }
        return $response;
    }

    private function getPostEsQuery($posts) {
        $query = [
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["type" => "box"]],
                        ["terms" => ["status" => ["A", "M", "F"]]]
                    ]
                ]
            ],
            "_source" => false,
            "size" => 0,
            "aggs" => ["posts" => [
                    "nested" => [
                        "path" => "box_posts"
                    ],
                    "aggs" => [
                        "filtered" => [
                            "filter" => ["bool" => ["must" => [
                                        ["terms" => ["box_posts.id" => $posts]]
                                    ]]],
                            "aggs" => [
                                "top_posts" => [
                                    "terms" => [
                                        "field" => "box_posts.id",
                                        "size" => 30,
                                        "order" => [
                                            "created_at_order" => "desc"
                                        ]
                                    ],
                                    "aggs" => [
                                        "top_posts" => [
                                            "top_hits" => [
                                                "sort" => [
                                                    [
                                                        "box_posts.created_at" => [
                                                            "order" => "desc"
                                                        ]
                                                    ]
                                                ],
                                                "_source" => [
                                                    "includes" => [
                                                        "box_posts.id"
                                                    ]
                                                ],
                                                "size" => 4,
                                            ]
                                        ],
                                        "created_at_order" => [
                                            "max" => [
                                                "field" => "box_posts.created_at"
                                            ]
                                        ],
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]]
        ];
        return $query;
    }

}
