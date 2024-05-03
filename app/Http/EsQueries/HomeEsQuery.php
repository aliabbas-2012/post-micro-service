<?php

namespace App\Http\EsQueries;

use Elasticsearch\ClientBuilder;

/**
 * Purpose of this class to make ES Cache and Query
 * @author ali
 */
class HomeEsQuery extends \App\Http\EsQueries\BaseEsQuery {

    private $esIndex = "home";
    private $user_id;

    public function __construct($user_id) {
        $this->user_id = $user_id;
    }

    /**
     * 
     * @param type $inputs
     */
    public function prepareHomeQuery($inputs) {
        $body = [
            "size" => $inputs["limit"],
            "_source" => [
                "excludes" => ["user_id", "owner_id"]
            ],
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => [
                                "user_id" => $this->user_id
                            ]
                        ],
                        [
                            "term" => [
                                "status" => true
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                [
                    "id" => [
                        "order" => "desc"
                    ]
                ]
            ]
        ];
        if ($inputs["greater_than"] > 0) {
            $body["query"]["bool"]["must"][2] = [
                "range" => [
                    "id" => [
                        "gt" => $inputs["greater_than"]
                    ]
                ]
            ];
        } else if ($inputs["less_than"] > 0) {
            $body["query"]["bool"]["must"][2] = [
                "range" => [
                    "id" => [
                        "lt" => $inputs["less_than"]
                    ]
                ]
            ];
        }

        return $this->getEsQueryIndex($this->esIndex, "doc", $body);
    }

}
