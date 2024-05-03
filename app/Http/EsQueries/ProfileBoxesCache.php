<?php

namespace App\Http\EsQueries;

/**
 * Profile Boxes Cache
 * @author ali
 */
class ProfileBoxesCache {

    private $boxPermissions = [], $user_id;
    private $offset = 0, $limit = 20;

    use \App\Traits\BoxInfoPostEsTrait;

    public function __construct($user_id, $boxPermissions = array(), $offset = 0) {
        $this->user_id = $user_id;
        $this->boxPermissions = $boxPermissions;
        $this->offset = $offset;
    }

    public function prepareQuery($lazy = true) {
        $params = $this->prepareIndexQuery($lazy);
        $params["body"] = [
            "from" => $this->offset,
            "size" => $this->limit,
            "query" => $this->prepareBaseQuery(),
            "sort" => $this->prepareSortQuery(),
        ];

        return $params;
    }

    private function prepareIndexQuery($lazy = true) {
        $body = [
            'index' => $this->esProfileIndex,
            'type' => 'doc',
        ];
        if ($lazy) {
            $body['client'] = [
                'future' => 'lazy'
            ];
        }
        return $body;
    }

    private function prepareBaseQuery() {
        $query = [
            "bool" => [
                "must" => [
                    [
                        "term" => ["user_id" => $this->user_id]
                    ]
                ]
            ]
        ];
        //IF length approach to 3 it means user is current user
        //ELSE then user is other
        if (count($this->boxPermissions) < 3) {
            $query["bool"]["must"][] = [
                "terms" => ["status" => $this->boxPermissions]
            ];
            $query["bool"]["must"][] = [
                "range" => [
                    "post_count" => [
                        "gt" => 0
                    ]
                ]
            ];
        }
        return $query;
    }

    /**
     * 
     * @return type
     */
    private function prepareSortQuery() {
        return [
            [
                "indexed_at" => [
                    "order" => "desc"
                ]
            ]
        ];
    }

    /**
     * Prepare Cache
     * @param type $client
     * @param type $boxes
     */
    public function prepareCache($client, $boxes) {
        $params = [];
        foreach ($boxes as $box) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->esProfileIndex,
                    '_type' => 'doc',
                    "_id" => (int) $box["id"],
                    "routing" => 1
                ]
            ];

            $params['body'][] = $box;
        }
        return $responses = $client->bulk($params);
    }

}
