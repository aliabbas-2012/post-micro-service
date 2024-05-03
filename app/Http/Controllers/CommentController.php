<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\PostCommentRepository;
use App\Http\Requests\PostCommentRequest;
use App\Models\UserPost;
use App\Models\PostComment;
use Illuminate\Support\Facades\Gate;
use App\Transformers\PostCommentTransformer;
use App\Events\ActivityEvent;
use App\Events\EngagementEvent;
use Illuminate\Http\Request;
use App\Http\Requests\LoadPostComments;

/**
 * Description of CommentController
 *
 * @author rizwan
 */
class CommentController extends Controller {

    use \App\Traits\CommonTrait;
    use \App\Traits\PostTrait;
    use \App\Traits\MessageTrait;
    use \App\Traits\AWSTrait;
    use \App\Traits\SqsTrait;

    protected $comment;
    protected $postCommentTransformer;
    protected $mentionListTransform;

    public function __construct(PostCommentRepository $commentRepository, PostCommentTransformer $postCommentTransformer) {

        $this->mentionListTransform = app()->make(\App\Transformers\CommentPeolpeListTransformer::class);
        $this->comment = $commentRepository;

        $this->postCommentTransformer = $postCommentTransformer;

        parent::__construct();
    }

    /**
     * post new comment
     * @param PostCommentRequest $request
     * @return type
     */
    public function postComment(PostCommentRequest $request) {
        try {

            $user = $this->getCurrentUser($request);
            $inputs = $this->getInputData($request);
            $device_id = $request->header("device-id");
            $inputs['user_id'] = $user->id;
            $inputs['client_ip_address'] = $request->header('remote-ip');
            $post = $this->validateCommentPost($inputs['post_id']);
            // Temporarily implemented to test post detail
            if (false && !$this->validatePostViewPrivacy($post, $user)) {
                return $this->sendCustomResponse(trans('messages.postCommentPermissonError'));
            }
            $inputs["post_owner"] = $post['user_id'];

            $comment = $this->comment->saveComent($inputs, $device_id);
            if ($comment instanceof PostComment) {

                if (env('APP_ENV') != 'local') {
//                    $this->triggerMlSqs(['user' => $user, 'post' => $post], $comment, false);
                    event(new ActivityEvent(['model' => $comment, 'user' => $user], 'post_comment'));
                    event(new EngagementEvent(['object_id' => $post['id'], 'type' => 'PI', 'interactor_id' => $inputs['user_id'], 'interaction_id' => $comment->id]));
                }

                if ($this->isNewPost($request->header('user-agent'))) {
                    $client = new \App\Transformers\GenericPostTransformer();
                    return $this->setStatusCode(200)->respondWithArray(['data' => $client->prepareLoadComments([$comment])[0]]);
                } else {
                    return $this->setStatusCode(200)->respondWithItem($comment, $this->postCommentTransformer);
                }
            }
            return $this->sendCustomResponse(trans('messages.generalError'));
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * archive / delete comment
     * @param PostCommentRequest $request
     * @return type
     */
    public function archiveComment(PostCommentRequest $request) {
        try {
            $response = $this->sendCustomResponse(trans('messages.commentDelePermissonError'), 400);
            $user = $this->getCurrentUser($request);
            $comment = $this->comment->getCommentById($request->get('data')['comment_id']);
            if ($comment instanceof PostComment && $this->isInArray([$comment->user_id, $comment->post->user_id], $user->id)) {
                if ($this->comment->archiveComment($comment)) {
                    \App\Models\Reporter::deleteReportedComment($comment->id);
//                    $isTrigger = ($user->id == $comment->user_id) ? $this->triggerMlSqs(['user' => $user, 'post' => $comment->post], $comment, true) : false;
                    return $this->sendCustomResponse(trans('messages.commentDelete'), 200);
                }
                $response = $this->sendCustomResponse(trans('messages.generalError'), 400);
            }
            return $response;
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Load post comments for load more
     * @param LoadPostComments $request
     * @return type
     */
    public function loadPostComments(LoadPostComments $request) {
        try {
            $user = $this->getCurrentUser($request);
            $comments = $this->comment->loadMoreComments($this->getInputData($request));
            if ($this->isNewPost($request->header('user-agent'))) {
                $client = new \App\Transformers\GenericPostTransformer();
                return $this->setStatusCode(200)->respondWithArray(['data' => $client->prepareLoadComments($comments["comments"]), 'total_comments' => $comments['total_comments'], 'total_likes' => $comments['total_likes']]);
            } else {
                return $this->setStatusCode(200)->respondWithCollection($comments["comments"], $this->postCommentTransformer, ['total_comments' => $comments['total_comments'], 'total_likes' => $comments['total_likes']]);
            }
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Load comment mention people list
     * @param Request $request
     * @return type
     */
    public function commentPeopleList(Request $request) {
        try {
            $user = $this->getCurrentUser($request);
            $inputs = $request->all();
            $inputs['search_key'] = isset($inputs['search_key']) ? $this->trim_string($inputs['search_key']) : "";
            $inputs = $this->prepareRequestInputs($inputs, ['lat', 'lon', 'post_id', 'offset', 'limit', 'search_key']);
            $inputs = $this->processLatLon($inputs, $request->header('remote-ip'));
            $users = $this->comment->getCommentPeopleList($user->id, $inputs);
            return $this->setStatusCode(200)->respondWithCollection($users, $this->mentionListTransform);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    private function processLatLon($inputs, $ip = null) {
        if (!isset($inputs["lat"]) || empty($inputs["lat"]) || $inputs["lat"] == 0.0) {
            if ($location = (new \App\Helpers\GuzzleHelper())->getIpLocation($ip)) {
                $inputs["lat"] = $location["latitude"];
                $inputs["lon"] = $location["longitude"];
            } else {
                $inputs["lat"] = 0.0;
                $inputs["lon"] = 0.0;
            }
        }
        return $inputs;
    }

}
