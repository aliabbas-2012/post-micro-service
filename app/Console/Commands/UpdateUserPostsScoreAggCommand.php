<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserPost as Post;

class UpdateUserPostsScoreAggCommand extends Command {

    use \App\Traits\CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_user_posts_agg_score:es';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $client;

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
        $this->getData();
    }

    public function getData() {
        $query = $this->prepareEsQuery();
        $resp = $this->client->count($query);
        $limit = 1000;
        if ($resp["count"] > 0) {
            $total_pages = ceil($resp["count"] / $limit);
            $offset = 0;
            for ($i = 0; $i < $total_pages; $i++) {
                $query = $this->prepareBoxQuery($offset, $limit);
                $data = $this->client->search($query);
                $this->processData($data);
                $offset = $offset + $limit;
                echo $offset;
                echo "\n";
                sleep(1);
            }
        }
    }

    public function processData($data) {

        if ($data["aggregations"]["boxes"]["sum_other_doc_count"] > 0) {
            //processing and recursive
            $this->updateToEs($data["aggregations"]["boxes"]["buckets"]);
//            $this->processData();
        }
    }

    private function prepareBoxQuery($from, $size) {
        $query = $this->prepareEsQuery();
        $query["body"]["size"] = 0;
        $query["body"]["from"] = $from;
        $query["body"]["aggs"] = [
            "boxes" => [
                "terms" => [
                    "size" => 100,
                    "field" => "db_id"
                ],
                "aggs" => [
                    "posts" => [
                        "nested" => ["path" => "box_posts"],
                        "aggs" => [
                            "top_posts" => [
                                "terms" => [
                                    "size" => 100,
                                    "field" => "box_posts.id"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $query;
    }

    private function prepareEsQuery() {
        $body = ["query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["type" => "box"]],
                        [
                            "nested" => [
                                "path" => "box_posts",
                                "query" => [
                                    "bool" => [
                                        "must" => [
                                            ["exists" => ["field" => "box_posts.id"]]
                                        ],
                                        "must_not" => [
                                            ["exists" => ["field" => "box_posts.score"]]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $this->prepareEsSearchIndex('trending', 'doc', $body);
    }

    public function updateToEs($buckets) {
        $data = ["box_ids" => [], "posts" => [], "post_ids" => []];

        foreach ($buckets as $bucket) {

            $data["box_ids"][] = "bx-" . $bucket["key"];
            foreach ($bucket["posts"]["top_posts"]["buckets"] as $post_bucket) {
                $data["post_ids"][$post_bucket["key"]] = $post_bucket["key"];
            }
        }

        $posts = Post::select("id", "score")
                        ->whereIn("id", array_values($data["post_ids"]))->get();
        foreach ($posts as $post) {
            $data["posts"][] = ["id" => $post->id, "score" => (float) $post->score];
        }
        $this->updateBoxesPostInEs($data);
    }

    private function updateBoxesPostInEs($data) {
        $body = [
            "script" => [
                "source" => "Map posts=new HashMap();for(int i=0;i<params.posts.size();i++){posts.put(params.posts.get(i).id,params.posts.get(i).score);}
for(int i=0;i<ctx._source.box_posts.size();i++){int post_id=ctx._source.box_posts.get(i).id;if(posts.containsKey(post_id)){ctx._source.box_posts.get(i).score=posts.get(post_id);}}",
                "lang" => "painless",
                "params" => ["posts" => $data["posts"]]],
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["type" => "box"]],
                        ["terms" => ["id" => $data["box_ids"]]],
                        ["nested" => ["path" => "box_posts",
                                "query" => [
                                    "bool" => [
//                                            "must" => [
////                                                [
////                                                    "terms" => [
////                                                        "box_posts.id" => $data["post_ids"]
////                                                    ]
////                                                ]
//                                            ],
                                        "must_not" => [["exists" => ["field" => "box_posts.score"]]]
                                    ]
                                ]
                            ]]
                    ]
                ]
            ]
        ];
        return $this->client->updateByQuery($this->prepareEsSearchIndex('trending', 'doc', $body));
    }

}
