<?php

namespace App\Traits;

use App\Models\PostSearchAttribute;
use App\Models\Location;
use Illuminate\Support\Facades\Redis;

/**
 * Description of EntityTrait
 * @author Rizwan + Ali Abbas
 */
trait EntityTrait {

    private $cacheSuperKey = "api-attributes";

    /**
     * 
     * @param type $apiInput
     */
    private function getAPIObject($id, $apiInput) {

        $cache_key = "{$apiInput["source_type"]}-{$apiInput["source_id"]}@{$apiInput["item_type_number"]}";
        $apiEntity = [];
        if ($cache = Redis::hGet($this->cacheSuperKey, $cache_key)) {
            $apiEntity = json_decode($cache, true);
            $apiEntity["archive"] = false;
            $apiEntity["is_allowed"] = true;
        } else {
            //Google Places
            if ($apiInput["item_type_number"] == 3 || $apiInput["item_type_number"] == 4) {
                $apiEntity = $this->findLocationEntityFromDb($id);
            } else {
                $apiEntity = $this->findApiEntityFromDb($id);
            }
        }
        return $apiEntity;
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    private function findLocationEntityFromDb($id) {
        if ($model = Location::getLocationByCol("id", $id)) {
            $apiEntity = $model->toArray();
            $apiEntity["archive"] = false;
            $apiEntity["is_allowed"] = true;
            $cache_key = "google-{$apiEntity["fs_location_id"]}@{$apiEntity["item_type_number"]}";
            $this->setAPInHset($cache_key, $apiEntity);
            return $apiEntity;
        }
        return false;
    }

    /**
     * 
     * @return type
     */
    private function findApiEntityFromDb($id) {
        if ($model = PostSearchAttribute::getAPIByCol('id', $id)) {
            $apiEntity = $model->toArray();
            $apiEntity["archive"] = false;
            $apiEntity["is_allowed"] = true;
            $cache_key = "{$apiEntity["source_type"]}-{$apiEntity["source_id"]}@{$apiEntity["item_type_number"]}";
            $this->setAPInHset($cache_key, $apiEntity);
            return $apiEntity;
        }
        return false;
    }

    /**
     * 
     * @param type $cache_key
     * @param type $apiEntity
     */
    private function setAPInHset($cache_key, $apiEntity) {
        Redis::hset($this->cacheSuperKey, $cache_key, json_encode($apiEntity, true));
    }

}
