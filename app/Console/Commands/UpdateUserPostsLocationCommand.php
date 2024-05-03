<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserPost as Post;

class UpdateUserPostsLocationCommand extends Command {

    use \App\Traits\CommonTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_user_posts_location:es';

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
            }
        }
    }

    public function processData($data) {

        if ($data["hits"]["total"] > 0) {
            //processing and recursive
            $this->updateToEs($data["hits"]["hits"]);
//            $this->processData();
        }
    }

    private function prepareBoxQuery($from, $size) {
        $query = $this->prepareEsQuery();
        $query["body"]["_source"] = [
            "db_id"
        ];
        $query["body"]["from"] = $from;
        $query["body"]["size"] = $size;
        $query["body"] ["sort"] = [
            ["db_id" => ["order" => "asc"]],
        ];
        $query["body"]["query"]["bool"]["must"][1]["nested"]["inner_hits"] = [
            "size" => 100,
            "_source" => [
                "box_posts.id",
                "box_posts.created_location"
            ]
        ];
        return $query;
    }

    private function prepareEsQuery() {
        $body = [
            "query" => [
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
                                            ["exists" => ["field" => "box_posts.created_location"]]
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

    public function updateToEs($hits) {
        $data = ["box_ids" => [], "posts" => [], "post_ids" => []];

        foreach ($hits as $hit) {

            $data["box_ids"][] = "bx-" . $hit["_source"]["db_id"];
            foreach ($hit["inner_hits"]["box_posts"]["hits"]["hits"] as $hit) {
                $data["post_ids"][$hit["_source"]["id"]] = $hit["_source"]["id"];
            }
        }

        $posts = Post::select("id", "client_ip_latitude", "client_ip_longitude")
                        ->whereIn("id", array_values($data["post_ids"]))->get();
        foreach ($posts as $post) {
            $data["posts"][] = ["id" => $post->id, "lat" => (float) $post->client_ip_latitude, "lon" => (float) $post->client_ip_longitude];
        }
        $this->updateBoxesPostInEs($data);
    }

    private function updateBoxesPostInEs($data) {
        $body = [
            "script" => [
                "source" => "Map posts=new HashMap();for(int i=0;i<params.posts.size();i++){Map loc=new HashMap();loc.put(params.loc.lat,params.posts.get(i).lat);loc.put(params.loc.lon,params.posts.get(i).lat);posts.put(params.posts.get(i).id,loc);}
                        for(int i=0;i<ctx._source.box_posts.size();i++){int post_id=ctx._source.box_posts.get(i).id;if(posts.containsKey(post_id)){ctx._source.box_posts.get(i).created_location=posts.get(post_id);}}",
                "lang" => "painless",
                "params" => [
                    "loc" => ["lat" => "lat", "lon" => "lon"],
                    "posts" => $data["posts"]
                ]
            ],
            "query" => [
                "bool" => [
                    "must" => [
                        ["term" => ["type" => "box"]],
                        ["terms" => ["id" => $data["box_ids"]]],
                        [
                            "nested" => [
                                "path" => "box_posts",
                                "query" => [
                                    "bool" => [
//                                        "must" => [
////                                                [
////                                                    "terms" => [
////                                                        "box_posts.id" => $data["post_ids"]
////                                                    ]
////                                                ]
//                                        ],
                                        "must_not" => [
                                            ["exists" => ["field" => "box_posts.created_location"]]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $this->client->updateByQuery($this->prepareEsSearchIndex('trending', 'doc', $body));
    }

}
