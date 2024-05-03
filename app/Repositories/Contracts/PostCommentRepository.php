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
interface PostCommentRepository {
    
    
    /**
     * save new comment
     * @param array $data
     */
    public function saveComent(array $data);
    
    /**
     * get single comment by id
     * @param type $id
     */
    public function getCommentById($id);
    
    /**
     * archive / delete comment
     * @param type $comment
     */
    public function archiveComment($comment);
    
    
}
