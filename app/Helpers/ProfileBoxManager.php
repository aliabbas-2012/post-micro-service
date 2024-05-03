<?php

namespace App\Helpers;

use App\Http\EsQueries\BoxPostGroup;
use App\Http\EsQueries\SearchBoxGroup;
use App\Http\EsQueries\ProfileBoxesCache;
use App\Transformers\BoxPostsGroupTransformer;
use App\Transformers\BoxGroupTransformer;
use Elasticsearch\ClientBuilder;

/**
 * Description of ProfileManager
 *
 * @author ali
 */
class ProfileBoxManager {

    private $current_user, $user_id, $boxPermissions, $client;
    private $boxGroupTransFormer, $boxPostGroupTransFormer;
    private $offset = 0;

    public function __construct($current_user, $user_id, $boxPermissions = array(), $offset = 0) {
        $this->current_user = $current_user;
        $this->boxPermissions = $boxPermissions;
        $this->user_id = $user_id;
        $this->client = $this->getEsClient(config("elastic_search.path"));
        $this->offset = $offset;
        $this->boxPostGroupTransFormer = app()->make(BoxPostsGroupTransformer::class);
        $this->boxGroupTransFormer = app()->make(BoxGroupTransformer::class);
    }

    private function getEsClient($host) {
        return ClientBuilder::create()->setHosts([$host])->build();
    }

    /**
     * 
     * @return boolean
     */
    public function loadProfileBoxes() {

        if ($this->current_user->id == $this->user_id) {
            return $this->loadPersonalProfileBoxes();
        } else {
            return $this->loadOtherProfileBoxes();
        }
        return false;
    }

    /**
     * 
     * @return type
     */
    public function loadPersonalProfileBoxes() {
        $boxGroupCache = new ProfileBoxesCache($this->user_id, $this->boxPermissions);
        $boxPostGroup = new BoxPostGroup($this->user_id, $this->boxPermissions);

        $future_mode = [];

        $future_mode["posts"] = $this->client->search($boxPostGroup->getLatestPosts());
        $future_mode["post_count"] = $this->client->search($boxPostGroup->getPostCount());
        $future_mode["cache_boxes"] = $this->client->search($boxGroupCache->prepareQuery());

        if ($resp = $this->loadFromCache($future_mode, $boxGroupCache)) {
            return $resp;
        } else {
            return $this->loadFromWithOutCache($future_mode, $boxGroupCache, $boxPostGroup);
        }
    }

    /**
     * PERSONL BOXES
     * @param type $future_mode
     * @param type $boxGroupCache
     * @return type
     */
    private function loadFromCache($future_mode, $boxGroupCache) {
        $resp = [];
        if ($future_mode["cache_boxes"]["hits"]["total"] > 0) {

            $resp = $this->boxPostGroupTransFormer->cacheTransform($future_mode);
            /**
             * Update Cache in ES in case of any update parm found
             */
            if (!empty($resp["update_able"])) {
                $search = $this->client->search($boxGroupCache->getLatestBoxPostQuery(array_values($resp["update_able"])));
                $boxesInfo = $this->boxPostGroupTransFormer->getUpdateCacheMedia($search);
                $params = $boxGroupCache->getUpdateProfileBoxCacheParamWithMedia($resp["update_able"], $boxesInfo, false);

                $this->client->bulk($params);
                $resp["boxes"] = $this->boxPostGroupTransFormer->updateBoxesResponse($resp["boxes"], $boxesInfo);
            }
        }
        return $resp;
    }

    /**
     * PERSONL BOXES
     * @param type $future_mode
     * @param type $boxGroupCache
     * @param type $boxPostGroup
     * @return type
     */
    private function loadFromWithOutCache($future_mode, $boxGroupCache, $boxPostGroup) {
        $future_mode_no_cache = [];

        $future_mode_no_cache["post_count"] = $future_mode["post_count"];
        $future_mode_no_cache["posts"] = $future_mode["posts"];
        $future_mode_no_cache["boxes"] = $this->client->search($boxPostGroup->getBoxes());

        $future_mode_no_cache["box_posts"] = $this->client->search($boxPostGroup->getBoxPost());

        if ($future_mode_no_cache["boxes"]["hits"]["total"] > 0) {

            $resp = $this->boxPostGroupTransFormer->getData($future_mode_no_cache);

            $boxGroupCache->prepareCache($this->client, $resp["boxes"]);
            $resp["boxes"] = array_slice($resp["boxes"], 0, 20);


            return $resp;
        } else {
            return [];
        }
    }

    /**
     * Load other profile
     * @return type
     */
    public function loadOtherProfileBoxes() {
        $boxPostGroup = new BoxPostGroup($this->user_id, $this->boxPermissions);

        $future_mode = [];
        $future_mode["boxes"] = $this->client->search($boxPostGroup->getBoxes());
        $future_mode["box_posts"] = $this->client->search($boxPostGroup->getBoxPost());

        $future_mode["posts"] = $this->client->search($boxPostGroup->getLatestPosts());
        $future_mode["post_count"] = $this->client->search($boxPostGroup->getPostCount());

        if ($future_mode["boxes"]["hits"]["total"] > 0) {
            $resp = $this->boxPostGroupTransFormer->getData($future_mode);
            return $resp;
        } else {
            return [];
        }
    }

    /**
     * 
     * @param type $inputs
     * @return type
     */
    public function searchBoxes($inputs) {
        $boxPostGroup = new SearchBoxGroup($this->user_id, $this->boxPermissions);
        $boxPostGroup->setSearchKey($inputs);
        \Log::info("---- load boxes elasticsearch query ------");
        \Log::info(json_encode($boxPostGroup->getBoxes()));
        $future["boxes"] = $this->client->search($boxPostGroup->getBoxes());
        $future["box_posts"] = $this->client->search($boxPostGroup->getBoxPost());
        if ($future["boxes"]["hits"]["total"] > 0) {
            return $future;
        }
        return [];
    }

    /**
     * 
     * @return type
     */
    public function loadMoreBoxes() {

        if ($this->current_user->id == $this->user_id) {
            return $this->loadMorePersonalBoxes();
        } else {
            return $this->loadMoreOtherBoxes();
        }
        return false;
    }

    /**
     * Separate case for android 
     * @return boolean
     */
    private function loadMorePersonalBoxes() {
        $boxGroupCache = new ProfileBoxesCache($this->user_id, $this->boxPermissions, $this->offset);
        $resp = $this->client->search($boxGroupCache->prepareQuery(false));
        if ($resp["hits"]["total"] > 0) {
            $result = $this->boxGroupTransFormer->cacheTransform($resp);
            return ["boxes" => $result, "total" => $resp["hits"]["total"]];
        } else if ($this->offset == 0) {
            $boxPostGroup = new BoxPostGroup($this->user_id, $this->boxPermissions);
            $mode["boxes"] = $this->client->search($boxPostGroup->getBoxes());
            $mode["box_posts"] = $this->client->search($boxPostGroup->getBoxPost());
            if ($mode["boxes"]["hits"]["total"] > 0) {
                $data = $this->boxPostGroupTransFormer->getData($mode, false);
                $boxGroupCache->prepareCache($this->client, $data["boxes"]);
                $resp = array_slice($data["boxes"], 0, 20);
                return ["boxes" => $resp, "total" => $mode["boxes"]["hits"]["total"]];
            }
        }
        return [];
    }

    /**
     * 
     * @return type
     */
    private function loadMoreOtherBoxes() {

        $boxPostGroup = new BoxPostGroup($this->user_id, $this->boxPermissions, 0, $this->offset);
        $future = [];

        $future["boxes"] = $this->client->search($boxPostGroup->getBoxes());
        $future["box_posts"] = $this->client->search($boxPostGroup->getBoxPost());

        if ($future["boxes"]["hits"]["total"] > 0) {
            return ["boxes" => $this->boxGroupTransFormer->transform($future), "total" => $future["boxes"]["hits"]["total"]];
        }
        return [];
    }

}
