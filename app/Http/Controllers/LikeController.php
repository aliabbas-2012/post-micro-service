<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers;

use App\Transformers\HomePostsTransformer;
use App\Transformers\PostTransformer;
use App\Models\Like;


/**
 * Like Controller
 * @author ali.abbas
 */
class LikeController extends Controller {

    use \App\Traits\CommonTrait;
    use \App\Traits\MessageTrait;
    use \App\Traits\PostTrait;

    protected $post;
    protected $PostTagUserTransformer;
    protected $esClient, $homeEsCache;


    public function __construct() {

        $this->homePostTransFormer = app()->make(HomePostsTransformer::class);
        $this->postTransformer = app()->make(PostTransformer::class);

        parent::__construct();
    }

    /**

     * Get home posts
     * @param HomeRequest $request
     * @return type
     */
    public function index(Request $request) {
        try {
            $user = $this->getCurrentUser($request);
            $query = Like::getLikePosts($user->id);
            $likePosts = $query->get();
            if (!$likePosts->isEmpty()) {
                $data = $this->homePostTransFormer->transformCollection($likePosts, $user->id, 40);
                return $this->setStatusCode(200)->respondWithArray(["data" => $data["response"]]);
            } else {
                return $this->setStatusCode(400)->respondWithArray(["message" => $this->errors['noResultFound'], "error_type" => 0]);
            }
            return $this->sendCustomResponse($this->errors['generalError'], 400);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex, 400, "home");
        }
    }

}
