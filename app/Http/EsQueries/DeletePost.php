<?php

namespace App\Http\EsQueries;

/**
 * Delete post from ES
 * @author ali
 */
class DeletePost extends BaseEsQuery {

    use \App\Traits\BoxInfoPostEsTrait;

    public function getDeleteBoxPostBody($model) {

        $params = [
            'index' => 'trending',
            'type' => 'doc',
            'client' => [
                'future' => 'lazy'
            ],
            'body' => [
                "conflicts" => "proceed",
                "script" => [
                    "source" => "for (int i = 0; i < ctx._source.box_posts.size();i++){if(ctx._source.box_posts[i].id == params.post_id){ctx._source.box_posts.remove(i);}}",
                    "lang" => "painless",
                    'params' => [
                        'post_id' => $model->id
                    ],
                ],
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "term" => ["type" => "box"]
                            ],
                            [
                                "nested" => $this->prepareNestedPostQuery($model->id)
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $params;
    }

    /**
     * 
     * @param type $post_id_or_ids
     * @return array
     */
    private function prepareNestedPostQuery($post_id_or_ids) {
        $query = [
            "path" => "box_posts",
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "exists" => ["field" => "box_posts.id"]
                        ],
                    ]
                ]
            ]
        ];

        if (is_array($post_id_or_ids)) {
            $query["query"]["bool"]["must"][] = [
                "terms" => ["box_posts.id" => array_values($post_id_or_ids)]
            ];
        } else {
            $query["query"]["bool"]["must"][] = [
                "term" => ["box_posts.id" => $post_id_or_ids]
            ];
        }
        return $query;
    }

    public function getBulkDeletePostBody($post_ids) {

        $body = [
            "query" => [
                "bool" => [
                    "should" => [
                        [
                            "bool" => [
                                "must" => [
                                    [
                                        "term" => [
                                            "type" => "post"
                                        ]
                                    ],
                                    [
                                        "terms" => [
                                            "id" => array_values($post_ids)
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $this->getEsQueryIndex('trending', 'doc', $body, true);
    }

    public function getBulkBoxPostDeleteBody($post_ids) {
        $body = [
            "conflicts" => "proceed",
            "script" => [
                "source" => "HashSet post_ids_h=new HashSet();post_ids_h.addAll(params.posts);for(int i=0;i<ctx._source.box_posts.size();i++){if(post_ids_h.contains(ctx._source.box_posts[i].id)){ctx._source.box_posts.remove(i);}}",
                "lang" => "painless",
                'params' => [
                    'posts' => $post_ids
                ],
            ],
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => ["type" => "box"]
                        ],
                        [
                            "nested" => $this->prepareNestedPostQuery($post_ids)
                        ]
                    ]
                ]
            ]
        ];
        return $this->getEsQueryIndex('trending', 'doc', $body);
    }

    /**
     * Method is no longer used
     * @param type $user_id
     * @param type $box_ids
     * @return string
     */
    public function getBoxCountDecrementQuery($user_id, $box_ids = []) {
        $params = [
            'index' => 'users',
            'type' => 'user',
            "id" => $user_id,
            'body' => [
                "conflicts" => "proceed",
                "script" => [
                    "source" => "HashSet box_ids_h=new HashSet();box_ids_h.addAll(params.box_ids);for(int i=0;i<ctx._source.boxes.size();i++){if(box_ids_h.contains(ctx._source.boxes[i].id)){ctx._source.boxes.get(i).post_count--;}}",
                    "lang" => "painless",
                    'params' => [
                        'box_ids' => $box_ids
                    ],
                ]
            ]
        ];

        return $params;
    }

}
