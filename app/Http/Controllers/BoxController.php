<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PostBox;
use App\Models\Box;
use App\Http\Requests\PostProfileIdValidation;
use App\Http\Requests\LoadMoreBoxesRequest;
use App\Transformers\BoxPostsGroupTransformer;
use App\Transformers\BoxGroupTransformer;
use App\Transformers\UserPostsTransformer;
use App\Helpers\ProfileBoxManager;
use App\Http\EsQueries\BoxPostGroup;
use App\Http\EsQueries\BoxPosts;
use App\Http\EsQueries\Box as BoxEs;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Contracts\BoxBookmarkRepository;
use App\Helpers\LambdaCaller;
use App\Helpers\GuzzleHelper;

class BoxController extends Controller {

    use \App\Traits\CommonTrait;

    use \App\Traits\MessageTrait;

    use \App\Traits\BoxTrait;

    protected $boxPostGroupTransFormer, $boxGroupTransFormer;
    protected $homePostTransFormer;
    protected $boxPostTransFormer;
    private $box = null;
    protected $post;
    protected $boxBookmarkRepository;
    private $isFollowed = false;
    private $inputs = [];

    public function __construct(Request $request) {
        $this->inputs = $request->all();
        $this->post = $this->initRepository(\App\Repositories\EloquentPost::class, PostBox::class);
        $this->boxPostGroupTransFormer = app()->make(BoxPostsGroupTransformer::class);
        $this->boxGroupTransFormer = app()->make(BoxGroupTransformer::class);
        $this->userPostsTransformer = app()->make(UserPostsTransformer::class);
        $this->homePostTransFormer = app()->make(\App\Transformers\HomePostsTransformer::class);
        $this->boxPostTransFormer = app()->make(\App\Transformers\BoxPostsTransformer::class);
        $this->boxBookmarkRepository = app()->make(BoxBookmarkRepository::class);
        parent::__construct();
    }

    /**
     * Internal service
     * @param Request $request
     */
    public function getBoxes(Request $request) {
        try {
            $current_user = $this->getCurrentUser($request);

            //prepare Object
            $box_permissions = config("general.box_permissions." . $request->get("status"));
            $this->boxPostGroupTransFormer->setBoxPermission($box_permissions);

            $profileBoxManager = new ProfileBoxManager($current_user, $request->get("user_id"), $box_permissions);

            if ($resp = $profileBoxManager->loadProfileBoxes()) {

                return $this->setStatusCode(200)->respondWithArray(["data" => $resp]);
            } else {
                return $this->sendCustomResponse([], 204);
            }
        } catch (\Exception $ex) {
            $this->logException($ex, "BoxController => getBoxes", $request->all());
            return $this->sendCustomResponse($ex->getMessage(), 400);
        }
    }

    /**
     *
     * @param Request $request
     */
    public function getBoxesLoadMore(LoadMoreBoxesRequest $request) {
        try {
            $current_user = $this->getCurrentUser($request);
            $user_id = $current_user->id;
            $box_permissions = $this->getUserPostPermissions();
            if ($this->isOhterUser($this->inputs, $current_user)) {
                if (!$user = $this->isOTherUserValid($this->inputs, $this->getUserColumns($user_id))) {
                    return $this->sendCustomResponse($this->errors['profileNotFound'], 400);
                }
                $box_permissions = $this->getUserPostPermissions($user);
                $user_id = $user->id;
            }
            $offset = !empty($this->inputs["offset"]) ? $this->inputs["offset"] : 0;
            \Log::info("--- load boxes inputs ---  {$current_user->username}");
            \Log::info(print_r($this->inputs, true));
            if (\App\Models\SyncFailur::isUserPostFailureExits($user_id, $current_user->id)) {
                $search_key = !empty($this->inputs["search_key"]) ? $this->inputs["search_key"] : "";
                $response = $this->getClassClient(GuzzleHelper::class)->searchUserBoxes($request, $user_id, $this->getPermissionStatus($box_permissions), $search_key, $offset);
                return !isset($response['error']) ? $this->setStatusCode(200)->respondWithArray($response) : $this->sendCustomResponse($this->errors[$response['error']], 404);
            } else {
                $profileBoxManager = new ProfileBoxManager($current_user, $user_id, $box_permissions, $offset);
                if (!empty($this->inputs["search_key"])) {
                    if ($future = $profileBoxManager->searchBoxes($this->inputs)) {
                        return $this->setStatusCode(200)->respondWithArray(["data" => $this->boxGroupTransFormer->transform($future)]);
                    }
                } else if ($response = $profileBoxManager->loadMoreBoxes()) {
                    return $this->setStatusCode(200)->respondWithArray(["data" => $response["boxes"], "total_boxes" => (int) $response["total"]]);
                }
            }
            return $this->sendCustomResponse($this->errors['noResultFound'], 404);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * User Posts
     * @param Request $request
     */
    public function getUserPosts(PostProfileIdValidation $request) {
        try {
            $response = [];
            $current_user = $this->getCurrentUser($request);
            $login_id = $user_id = $current_user->id;
            $box_permissions = $this->getUserPostPermissions();
            $this->inputs["other_user"] = false;
            if ($this->isOhterUser($this->inputs, $current_user)) {
                if (!$user = $this->isOTherUserValid($this->inputs, $this->getUserColumns($user_id))) {
                    return $this->sendCustomResponse(trans('messages.profileNotFound'), 400);
                }
                $box_permissions = $this->getUserPostPermissions($user);
                $user_id = $user->id;
                $this->inputs["other_user"] = true;
                $this->inputs["current_user_id"] = $current_user->id;
            }
            if (\App\Models\SyncFailur::isUserPostFailureExits($user_id, $current_user->id)) {
                $isSamePreviewCall = $this->isSamePreviewCall($request);
                $response = $this->getClassClient(GuzzleHelper::class)->getUserPosts($request, $user_id, $this->inputs, [$this->getPermissionStatus($box_permissions), $login_id, $isSamePreviewCall]);
            } else {
                $client = $this->getEsClient(config("elastic_search.path"));
                $boxPostGroup = new BoxPostGroup($user_id, $box_permissions, $this->inputs["less_than"]);
                $posts = $client->search($boxPostGroup->getLatestPosts());
                $response = $this->boxPostGroupTransFormer->preparePostsResponse($posts["aggregations"]);
            }
            return !isset($response['error']) ? $this->setStatusCode(200)->respondWithArray(["data" => $response]) : $this->sendCustomResponse($this->errors[$response['error']], 400);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     *
     * @param Request $request
     */
    public function getPostPermissionCount(Request $request) {
        try {
            $current_user = $this->getCurrentUser($request);
            $user_id = $current_user->id;
            $box_permissions = $this->getUserPostPermissions();
            if ($this->isOhterUser($this->inputs, $current_user)) {
                if (!$user = $this->isOTherUserValid($this->inputs, $this->getUserColumns($user_id))) {
                    return $this->sendCustomResponse($this->errors['profileNotFound'], 400);
                }
                $box_permissions = $this->getUserPostPermissions($user);
                $user_id = $user->id;
            }
            $client = $this->getEsClient(config("elastic_search.path"));
            $boxPostGroup = new BoxPostGroup($user_id, $box_permissions);
            $data = $this->parseESData($client->search($boxPostGroup->getPostCount()));
            return $this->setStatusCode(200)->respondWithArray($data);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    private function parseESData($data) {
        $postCount = $data["aggregations"]["posts"]["post_count"]["value"];
        $boxCount = $data["hits"]["total"];
        return ["box_count" => $boxCount, "post_count" => $postCount];
    }

    private function getUserColumns($viewer_id) {
        return [
            "id", "is_live",
            \DB::raw("(Select status  from friends where follower_id =  " . $viewer_id . " and following_id = users.id) as follow_status"),
            \DB::raw("(Select id  from blocked_users where user_id =  " . $viewer_id . " and blocked_user_id = users.id) as blocked_status"),
            \DB::raw("(Select id  from blocked_users where blocked_user_id =  " . $viewer_id . " and user_id = users.id) as blocked_me_status"),
        ];
    }

    /**
     * get user post permission
     * @param type $user
     * @return type
     */
    public function getUserPostPermissions($user = []) {
        if (empty($user)) {
            return config("general.box_permissions.0");
        }
        if ($user->follow_status == "A") {
            return config("general.box_permissions.1");
        }
        if (!$user->is_private) {
            return config("general.box_permissions.2");
        }
    }

    /**
     * GET $_SERVER['http']/boxCount
     * @param Request $request
     * @return type
     */
    public function getBoxCount(Request $request) {
        $client = $this->getEsClient(config("elastic_search.path"));
        $boxEs = new BoxEs();
        $box = $boxEs->getBoxCount($client, $request->get("user_id"));
        return $this->sendCustomResponse($box["count"], 200);
    }

    /**
     *
     * @param Request $request
     */
    public function getBoxPosts(Request $request) {
        try {

            $current_user = $this->getCurrentUser($request);
            $inputs = $this->prepareBoxPostsInputs($request, $current_user);
            /**
             * making box_id from fsq
             */
            if (!empty($inputs["fsq"])) {
                $inputs['box_id'] = $this->post->getDynamoDbObject($inputs["fsq"]);
            }
            $validate = Validator::make($inputs, ['box_id' => 'required|integer|min:1']);
            if ($validate->fails()) {
                return $this->sendCustomResponse($validate->errors()->first(), 400);
            }

            if ($this->isUserAllowed($current_user, $inputs['box_id'])) {
                $permisions = $this->getBoxPermission($current_user);
                $isSave = ($inputs["offset"] <= 0 && $this->box["user_id"] != $current_user->id ) ? $this->saveView([$this->box, $current_user], "B", [$request->header("remote-ip"), $request->header('device-id')]) : false;
                return $this->processBoxPosts($inputs, $permisions, $current_user, $request);
            } else if (empty($this->box)) {
                //in this case box will be delete from es cache
                $this->removeEsBox($inputs['box_id'], $request->header("token"));
                return $this->sendCustomResponse(trans("messages.noBoxFound"), 400);
            }
            return $this->sendCustomResponse(trans('messages.boxViewPermissionError'), 400);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * 
     * @param type $box
     * @param type $user_id
     * @param type $inputs
     */
    private function preapreBoxUserInfo($box, $user_id, $inputs) {

        if ($box['user_id'] !== $user_id) {
            $inputs["other_user"] = true;
            $inputs["current_user_id"] = $user_id;
        } else {
            $inputs["other_user"] = false;
        }
        return $inputs;
    }

    private function processBoxPosts($inputs, $permisions, $current_user, $request) {
        $respose = [];
        if (\App\Models\SyncFailur::isBoxPostFailureExits($this->box['id'], $current_user->id)) {
            $inputs = $this->preapreBoxUserInfo($this->box, $current_user->id, $inputs);
            $respose = $this->getClassClient(GuzzleHelper::class)->getBoxPosts($request, $inputs, $this->getPermissionStatus($permisions));
            return !isset($respose['error']) ? $this->prepareBoxDetailResponse($respose, $inputs, 0) : $this->sendCustomResponse(trans("messages." . $respose['error']), 400);
        } else {
            $client = $this->getEsClient(config("elastic_search.path"));
            $boxPosts = new BoxPosts($inputs, $permisions);
            $search = $client->search($boxPosts->getLatestPosts());
            if ($resp = $this->boxPostGroupTransFormer->preparePostsResponse($search["aggregations"])) {
                return $this->prepareBoxDetailResponse($resp, $inputs, $search["aggregations"]["posts"]["doc_count"]);
            } else if (isset($search["aggregations"]["posts"]["filtered"]["doc_count"]) && $search["aggregations"]["posts"]["filtered"]["doc_count"] == 0) {
                return $this->prepareBoxDetailResponse([], $inputs, 0);
            }
        }
    }

    private function getPermissionStatus($permisions) {
        if (count($permisions) == 3) {
            return 0;
        } else if (count($permisions) == 2) {
            return 1;
        } else if (count($permisions) == 1) {
            return 2;
        } else {
            return 3;
        }
    }

    public function prepareBoxDetailResponse($response, $inputs, $count = 0) {
        $lambda = new LambdaCaller();
        $data["bookmark_status"] = $this->boxBookmarkRepository->isBookmarked($inputs["user_id"], $inputs["box_id"], "B");
        $data["id"] = $this->box["id"];
        $data["name"] = $this->box["name"];
        $data["status"] = $this->box["status"];
        if ($short_code = $lambda->updateShortCode($this->box)) {
            $data["short_code"] = $short_code;
            Box::where("id", $inputs["box_id"])->update(["short_code" => $short_code]);
        } else {
            $data["short_code"] = $this->box["short_code"];
        }

        $data["total"] = $count;
        $data["posts"] = $response;
        $data["user"] = $this->prepareUserWithStatus($this->box['user']);
        return ["data" => $data];
    }

    /**
     * get box permission
     * @param type $id
     * @param type $current_user
     * @return type
     */
    private function getBoxPermission($current_user) {
        $permissions = ['A'];
        if ($this->box['user_id'] == $current_user->id) {
            $permissions = ['A', 'F', 'M'];
        } else if ($this->isFollowed) {
            $permissions = ['A', 'F'];
        }
        return $permissions;
    }

    /**
     * check user post view permission
     * @param type $loginUser
     * @param type $box_id
     * @return boolean
     */
    private function isUserAllowed($loginUser, $box_id) {
        $response = false;
        if ($this->box = Box::getBoxById($box_id, $loginUser->id)) {
            \Log::info("=== privacy box founded ==");
            \Log::info($this->box);

            if ($this->box['user_id'] == $loginUser->id) {
                return true;
            } else if ($this->box["status"] == "M") {
                //private box of other user wont be able to see
                return false;
            }

            if (!\App\Models\BlockedUser::checkbBlockUser($loginUser->id, $this->box['user_id'])) {
                $this->isFollowed = \App\Models\Friend::isFolloweduser($loginUser->id, $this->box['user_id']);
                if ($this->isFollowed && in_array($this->box["status"], ["A", "F"])) {
                    $response = true;
                }
                if ($this->box['user']['is_live'] == 1 && in_array($this->box["status"], ["A"])) {
                    $response = true;
                }
            }
        } else {


            \Log::info("=== privacy box not founded ==");
        }
        return $response;
    }

}
