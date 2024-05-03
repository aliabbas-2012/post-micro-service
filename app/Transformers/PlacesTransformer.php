<?php

namespace App\Transformers;

/**
 * Response handling for Place
 * @author ali
 */
class PlacesTransformer extends PostTransformer {

    public function transform($placeGroup) {
        return $this->preparePlaceResponse($placeGroup);
    }

    private function preparePlaceResponse($placeGroup) {
        //getting first place
        $place = $placeGroup[0];
        if (!empty($place)) {
            $posts = $this->preparePostsResponse($placeGroup);
            return [
                "id" => $place->id,
                "name" => $place->location_name,
                "address" => $place->address,
                "latitude" => $place->latitude,
                "longitude" => $place->longitude,
                "fs_location_id" => $place->fs_location_id,
                "thumb" => $place->post->postMedia[0]->thumb,
                "posts" => $posts
            ];
        }
    }

    private function preparePostsResponse($placeGroup) {
        $data = [];
        foreach ($placeGroup as $place) {
            $data[] = $this->prepareSimplePostResponse($place->post);
        }
        return $data;
    }

}
