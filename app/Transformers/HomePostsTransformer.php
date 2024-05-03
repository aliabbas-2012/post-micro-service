<?php

namespace App\Transformers;

/**
 * Response handling for HomePosts
 * @author ali
 */
class HomePostsTransformer extends PostTransformer {

    public $cache_index = "home";

    public function transform($homePost) {
        return $this->preparePostResponseWithRelations($homePost->post);
    }

    /**
     * To Fetch array
     * @param type $posts
     * @return array
     */
    public function transformCollection($posts, $user_id, $limit) {
        $data = ["response" => [], "bulk" => []];
        $params = [];
        foreach ($posts as $k => $homePost) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->cache_index,
                    '_type' => 'doc',
                    '_id' => $homePost->user_posts_id . "-" . $user_id,
                    "_routing" => $user_id
                ]
            ];
            $model = $this->preparePostResponseWithRelations($homePost);
            //it means if user sends 40 then only 0 to 39 key will be added in user response
            if ($k < $limit) {
                array_push($data["response"], $model);
            }
            $model["user_id"] = $user_id;
            $model["owner_id"] = $homePost->user_id;
            $model["status"] = true;
            $params['body'][] = $model;
        }
        $data["bulk"] = $params;
        return $data;
    }

}
