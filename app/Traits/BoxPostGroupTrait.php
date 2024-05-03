<?php

namespace App\Traits;

/**
 * Used to contains extra functions of BoxPostGroup
 *
 * @author Ali
 */
trait BoxPostGroupTrait {

    private function prepareBoxData($search) {
        $data = [];
        if ($search["hits"]["total"] > 0) {
            foreach ($search["hits"]["hits"] as $hit) {
                $data[] = ["id" => str_replace("bx-", "", $hit["_source"]["id"]), "name" => $hit["_source"]["name"], "status" => $hit["_source"]["status"]];
            }
        }
        return $data;
    }

    private function prepareBoxGroupData($search) {
        $data = [];
        if ($search["hits"]["total"] > 0) {
            foreach ($search["hits"]["hits"] as $hit) {

                $data[str_replace("bx-", "", $hit["_id"])]["post_count"] = $hit["inner_hits"]["box_posts"]["hits"]["total"];
                $data[str_replace("bx-", "", $hit["_id"])]["posts"] = $this->prepareBoxGroupPostData($hit);
            }
        }
        return $data;
    }

    private function prepareBoxGroupPostData($hit) {
        $data = [];

        if (!empty($hit["inner_hits"]["box_posts"]["hits"]) && $hit["inner_hits"]["box_posts"]["hits"]["total"] > 0) {
            foreach ($hit["inner_hits"]["box_posts"]["hits"]["hits"] as $inner) {
                $data[] = ["file" => $inner["_source"]["post_media"][0]["file"], "file_type_number" => $inner["_source"]["post_media"][0]["file_type_number"]];
            }
        }
        return $data;
    }

    private function getBoxAggFields() {
        return [
            "name",
            "box_posts.id",
            "box_posts.post_type_id",
            "box_posts.location",
            "box_posts.post_attributes",
            "box_posts.created_at",
            "box_posts.post_media.file",
            "box_posts.post_media.file_base_name",
            "box_posts.post_media.file_width",
            "box_posts.post_media.file_height",
            "box_posts.post_media.medium_file_width",
            "box_posts.post_media.medium_file_height",
            "box_posts.post_media.bucket",
            "box_posts.post_media.bg_color",
            "box_posts.post_media.file_type_number",
        ];
    }

}
