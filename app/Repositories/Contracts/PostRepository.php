<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories\Contracts;

/**
 * Description of CommentRepository
 *
 * @author rizwan
 */
interface PostRepository {

    /**
     * Get single active post
     * @param type $id
     */
    public function getSinglePost(array $data);
}
