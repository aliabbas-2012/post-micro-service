<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Transformers;

use \Carbon\Carbon;

/**
 * BoxPostsGroupTransformer
 * @author Ali
 */
class BoxPostsGroupTransformer extends PostTransformer {

    private $box_permissions = [];

    //This condtion is permanently for making sure object inside box permissions
    public function transform($boxes) {
        return array_slice(array_values(array_filter($boxes, function($b) {
                            if (isset($this->box_permissions[$b["status"]]))
                                return $b;
                        })), 0, 20);
    }

    public function getData($collection, $profile = true) {

        $box_groups = $this->prepareBoxGroupData($collection["box_posts"]);
        $resp = [];
        foreach ($collection["boxes"]["hits"]["hits"] as $source) {

            $box = $source["_source"];
            $data = [
                "id" => $box['db_id'],
                "name" => $box["name"],
                "status" => $box["status"],
                "user_id" => (int) str_replace("u-", "", $box["user_id"]),
                'post_count' => isset($box_groups[$box["db_id"]]) ? $box_groups[$box["db_id"]]["post_count"] : 0,
                'score' => isset($box_groups[$box["db_id"]]) ? $box_groups[$box["db_id"]]["post_count"] * 999999 : $box['db_id'],
                "is_update" => false,
                "media" => [],
            ];

            if (!empty($box_groups[$box["db_id"]]["posts"])) {
                $data["indexed_at"] = $box_groups[$box["db_id"]]["posts"][0]["created_at"];

                $data["media"] = $this->getPostMediaResponse($box_groups[$box["db_id"]]["posts"]);
            } else {

                $data["media"] = $this->getEmptyBoxMedia();
            }
            $resp[] = $data;
        }
        // in case of profile (default) all data will be sent
        if ($profile) {
            $post_count = isset($collection["post_count"]["aggregations"]["posts"]["post_count"]) ? $collection["post_count"]["aggregations"]["posts"]["post_count"]["value"] : 0;
            return ["boxes" => $resp, "box_count" => $collection["boxes"]["hits"]["total"],
                "post_count" => $post_count,
                "posts" => $this->preparePostsResponse($collection["posts"]["aggregations"])];
        } else {
            return ["boxes" => $resp];
        }
    }

    /**
     * Transform via Cache
     * @param type $collection
     * @return type
     */
    public function cacheTransform($collection) {
        $post_count = isset($collection["post_count"]["aggregations"]["posts"]["post_count"]) ? $collection["post_count"]["aggregations"]["posts"]["post_count"]["value"] : 0;
        $resp = $this->getCacheResp($collection);
        return ["boxes" => $resp["boxes"], "update_able" => $resp["update_able"], "box_count" => $collection["cache_boxes"]["hits"]["total"],
            "post_count" => $post_count, "posts" => $this->preparePostsResponse($collection["posts"]["aggregations"])];
    }

    /*
     * Will help to to make response 
     */

    private function getCacheResp($collection) {
        $data = ["boxes" => [], "update_able" => []];
        foreach ($collection["cache_boxes"]["hits"]["hits"] as $hit) {
            $data["boxes"][] = $hit["_source"];
            if (isset($hit["_source"]["is_update"]) && $hit["_source"]["is_update"]) {
                $data["update_able"][(int) $hit["_id"]] = (int) $hit["_id"];
            }
        }
        return $data;
    }

    /**
     * This method will only deal with cache
     * @param type $collection
     * @return type
     */
    public function getUpdateCacheMedia($collection) {
        $data = [];
        foreach ($collection["hits"]["hits"] as $hit) {
            if (!empty($hit["inner_hits"]["box_posts"]["hits"]["hits"])) {
                $box_id = str_replace("bx-", "", $hit["_id"]);
                $data[$box_id] = ["post_count" => $hit["inner_hits"]["box_posts"]["hits"]["total"]];
                $media = [];
                foreach ($hit["inner_hits"]["box_posts"]["hits"]["hits"] as $k => $inner) {
                    if ($k == 0) {
                        $data[$box_id]["indexed_at"] = $inner["_source"]["created_at"];
                    }
                    $media[$k]['bucket'] = isset($inner["_source"]["post_media"][0]["bucket"]) ? $inner["_source"]["post_media"][0]["bucket"] : "";
                    $media[$k]['thumb'] = $this->getBoxMediaURLs($inner["_source"]["post_media"][0]);
                    $media[$k]['medium'] = $this->getBoxMediaURLs($inner["_source"]["post_media"][0], 'medium');
                }
                $data[$box_id]["media"] = $media;
            }
        }
        return $data;
    }

    /**
     * 
     * @param type $boxes
     * @param type $boxesInfo
     * @return type
     */
    public function updateBoxesResponse($boxes, $boxesInfo) {
        foreach ($boxes as $key => $box) {
            if (!empty($boxesInfo[$box["id"]])) {
                $boxes[$key]["is_update"] = false;
                $boxes[$key]["indexed_at"] = $boxesInfo[$box["id"]]["indexed_at"];
                $boxes[$key]["post_count"] = $boxesInfo[$box["id"]]["post_count"];
                $boxes[$key]["media"] = $boxesInfo[$box["id"]]["media"];
            }
        }
        return $boxes;
    }

    /**
     * 
     * @param type $hits
     * @return type
     */
    public function getPostMediaResponse($hits) {
        $media = [];

        foreach ($hits as $key => $hit) {

            if (isset($hit["post_type_id"]) && $hit["post_type_id"] == config("general.post_type_index.search")) {

                $media[$key] = $this->getSearchPostResponse($hit);
            } else if (isset($hit["post_type_id"]) && $hit["post_type_id"] == config("general.post_type_index.location")) {
                $media[$key] = $this->getLocationPostResponse($hit);
            } else {
                $media[$key]['bucket'] = isset($hit["bucket"]) ? $hit["bucket"] : "";
                $media[$key]['thumb'] = $this->getBoxMediaURLs($hit);
                $media[$key]['medium'] = $this->getBoxMediaURLs($hit, 'medium');
            }
        }

        return $media;
    }

    /**
     * 
     * @param type $hit
     * @return type
     */
    public function getLocationPostResponse($hit) {

        return [
            "thumb" => $hit["location"]["thumbnail"],
            "medium" => $hit["location"]["thumbnail"]
        ];
    }

    /**
     * 
     * @param type $hit
     * @return type
     */
    public function getSearchPostResponse($hit) {
        $thumbs = [];
        try {

            $thumbs = ["thumb" => $hit["post_attributes"]["thumbnail"], "medium" => $hit["post_attributes"]["bg_image"]];
        } catch (\Exception $ex) {
            \Log::info("===============");
            \Log::info($hit);
            \Log::info($hit["post_attributes"]);
            \Log::info("=======--========");
        }

        if (!empty($hit["file"])) {
            $thumbs = ["thumb" => $this->getBoxMediaURLs($hit), "medium" => $this->getBoxMediaURLs($hit, 'medium')];
        }
        return [
            "thumb" => $thumbs["thumb"],
            "medium" => $thumbs["medium"],
            "item_type" => $hit["post_attributes"]["item_type"],
            "item_type_number" => $hit["post_attributes"]["item_type_number"],
        ];
    }

    public function getBoxMediaURLs($media = [], $type = 'thumb') {
        $resp = "";

        $media["bucket"] = !empty($media["bucket"]) ? $media["bucket"] : "fayvo_live";

        switch ($media['file_type_number']) {
            case 1:
                $resp = $this->getPostCdnUrl($media["bucket"]) . config('image.post_images_' . $type) . $media['file'];
                break;
            case 2:
                $resp = $this->getPostCdnUrl($media["bucket"]) . config('image.post_videos_' . $type) . str_replace(".mp4", ".jpg", $media['file']);
                break;
            default:
                "";
        }
        return $resp;
    }

    public function getEmptyBoxMedia() {
        return [
                [
                "thumb" => config('image.fayvo-sample-cdn-url') . config("image.empty_new_box_url"),
                "medium" => config('image.fayvo-sample-cdn-url') . config("image.empty_new_light_box_url"),
            ]
        ];
    }

    /**
     * 
     * @param type $boxesGroup
     * @param type $box_id
     */
    public function getBoxName($boxesGroup, $box_id) {
        if (isset($boxesGroup[$box_id][0])) {
            return $boxesGroup[$box_id][0]->name;
        }
        return "";
    }

    public function getBoxStatus($boxesGroup, $box_id) {
        if (isset($boxesGroup[$box_id][0])) {
            return $boxesGroup[$box_id][0]->status;
        }
        return "";
    }

    protected function prepareBoxGroupData($search) {
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
                if (isset($inner["_source"]["post_media"])) {
                    $data[] = [
                        "created_at" => $inner["_source"]["created_at"], "file" => $inner["_source"]["post_media"][0]["file"], "file_type_number" => $inner["_source"]["post_media"][0]["file_type_number"],
                        "bucket" => $inner["_source"]["post_media"][0]["bucket"],
                        "location" => isset($inner["_source"]["location"]) ? $inner["_source"]["location"] : [],
                        "post_type_id" => isset($inner["_source"]["post_type_id"]) ? $inner["_source"]["post_type_id"] : "",
                        "post_attributes" => isset($inner["_source"]["post_attributes"]) ? $inner["_source"]["post_attributes"] : []
                    ];
                } else {
                    $data[] = [
                        "created_at" => $inner["_source"]["created_at"],
                        "post_type_id" => isset($inner["_source"]["post_type_id"]) ? $inner["_source"]["post_type_id"] : "",
                        "location" => isset($inner["_source"]["location"]) ? $inner["_source"]["location"] : [],
                        "post_attributes" => isset($inner["_source"]["post_attributes"]) ? $inner["_source"]["post_attributes"] : []
                    ];
                }
            }
        }

        return $data;
    }

    public function preparePostsResponse($aggregations) {
        $data = [];

        if ($aggregations["posts"]["filtered"]["doc_count"] > 0) {
            foreach ($aggregations["posts"]["filtered"]["top_posts"]["buckets"] as $post_id => $bucket) {
                $post = $bucket["top_posts"]["hits"]["hits"][0]["_source"];


                if (isset($post["post_type_id"]) && $post["post_type_id"] == config("general.post_type_index.search")) {
                    $resp = $this->prepareSearchPostResponse($post);
                } else if (isset($post["post_type_id"]) && $post["post_type_id"] == config("general.post_type_index.location")) {
                    $resp = $this->prepareLocationTypesPostResponse($post);
                } else {
                    $post["post_type"] = $this->getPostType($post);
                    $post["media_type"] = $post["post_media"][0]["file_type_number"];
                    $post["post_media"][0]["bucket"] = !empty($post["post_media"][0]["bucket"]) ? $post["post_media"][0]["bucket"] : "fayvo_live";
                    $post["post_media"][0]["file_base_name"] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $post["post_media"][0]["file"]);
                    $post["post_media"] = collect((object) $post["post_media"]);

                    $resp = $this->prepareSimplePostResponse($post);
                }


                $data[] = $resp;
            }
        }
        return $data;
    }

    private function getPostType($post) {
        if (isset($post["post_type_id"]) && !empty($post["post_type_id"])) {
            return $post["post_type_id"];
        } else if (count($post["post_media"]) > 1) {
            return 5;
        } else {
            return $post["post_media"][0]["file_type_number"];
        }
    }

    public function prepareSimplePostResponse($post) {

        return [
            "id" => $post["id"],
            "created_at" => Carbon::parse($post["created_at"])->format("Y-m-d\TH:i:s\Z"),
                ] + $this->prepareSingleBoxMedia((object) $post["post_media"]->first(), $post["post_type"]);
    }

    public function prepareSearchPostResponse($post) {

        $post["post_type"] = $this->getPostType($post);
        $post["media_type"] = $post["post_media"][0]["file_type_number"];
        $post["post_media"][0]["bucket"] = !empty($post["post_media"][0]["bucket"]) ? $post["post_media"][0]["bucket"] : "fayvo_live";
        $post["post_media"][0]["file_base_name"] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $post["post_media"][0]["file"]);
        $post["post_media"] = collect((object) $post["post_media"]);

        $resp = $this->prepareSimplePostResponse($post);


        $resp["search"] = [
            "title" => $post["post_attributes"]["title"],
            "source_type" => $post["post_attributes"]["source_type"],
            "item_type_number" => $post["post_attributes"]["item_type_number"],
            "item_type" => $post["post_attributes"]["item_type"],
            "thumbnail" => $post["post_attributes"]["thumbnail"],
            "bg_image" => $post["post_attributes"]["bg_image"],
        ];
        return $resp;
    }

    /**
     * It contains new post types
     * @param type $post
     * @return type
     */
    public function prepareLocationTypesPostResponse($post) {
        $screen_shot = $this->getPostCdnUrl($post["location"]["bucket"]) . config('image.' . $post["location"]["bucket"] . '_post_location') . $post["location"]['file'];

        return [
            "id" => $post["id"],
            "thumbnail" => !empty($post["location"]['file']) ? $screen_shot : $post["location"]["thumbnail"],
            "post_type" => $post["post_type_id"],
            "location" => [
                "thumbnail" => $post["location"]['thumbnail']
            ],
            "width" => $post["location"]['width'],
            "height" => $post["location"]['height'],
            "created_at" => Carbon::parse($post["created_at"])->format("Y-m-d\TH:i:s\Z"),
        ];
    }

    /**
     * 
     * @param type $box_permissions
     */
    public function setBoxPermission($box_permissions) {
        $this->box_permissions = array_flip($box_permissions);
    }

}
