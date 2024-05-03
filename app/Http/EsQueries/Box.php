<?php

namespace App\Http\EsQueries;

/**
 * Purpose of this class to handle long ES Queries against model
 * @author ali
 */
class Box {

    /**
     * Get the latest posts under a user with permission with valid permissions
     * @param type $client
     * @param type $user_id
     * @param type $post_limit
     */
    public function getBoxCount($client, $user_id) {
        $params = [
            'index' => 'trending',
            'type' => 'doc',
            'body' => $this->prepareQuery($user_id)
        ];
        return $client->count($params);
    }

    /**
     * 
     * @param type $user_id
     * @return array
     */
    public function prepareQuery($user_id) {
        return [
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "term" => ["type" => "box"]
                        ],
                        [
                            "term" => ["user_id" => "u-" . $user_id]
                        ]
                    ]
                ]
            ],
        ];
    }

}
