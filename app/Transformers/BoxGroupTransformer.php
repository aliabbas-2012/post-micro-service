<?php

namespace App\Transformers;

/**
 * BoxGroupTransformer
 * @author Akku
 */
class BoxGroupTransformer extends BoxPostsGroupTransformer {

    public function transform($collection) {

        $box_groups = $this->prepareBoxGroupData($collection["box_posts"]);
        $resp = [];

        foreach ($collection["boxes"]["hits"]["hits"] as $source) {

            $box = $source["_source"];

            $data = [
                "id" => $box['db_id'],
                "name" => $box["name"],
                "status" => $box["status"],
                'post_count' => isset($box_groups[$box["db_id"]]) ? $box_groups[$box["db_id"]]["post_count"] : 0,
                "media" => [],
            ];

            if (!empty($box_groups[$box["db_id"]]["posts"])) {
                $data["media"] = $this->getPostMediaResponse($box_groups[$box["db_id"]]["posts"]);
            } else {
                $data["media"] = $this->getEmptyBoxMedia();
            }
            $resp[] = $data;
        }

        return $resp;
    }
    /**
     * 
     * @param type $collection
     * @return type
     */
    public function getCount($collection) {
        return $collection["boxes"]["hits"]["total"];
    }

    public function cacheTransform($collection) {
        return array_column($collection["hits"]["hits"], "_source");
    }

}
