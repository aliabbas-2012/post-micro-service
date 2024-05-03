<?php

namespace App\Transformers;

/**
 * BoxGroupTransformer
 * @author Akku
 */
class TopSearchPostTransformer extends BaseTransformer {

    public function transform($data) {
        $response = [];
        if (!empty($data) && !empty($data->selfSourcePost)) {
            if (isset($data->selfSourcePost->url_post)) {
                $response = $this->prepareUrlPostArr($data->selfSourcePost);
            } else {
                $response = $this->prepareSearchPostArr($data->selfSourcePost);
            }
        }
        return $response;
    }

}
