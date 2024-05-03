<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transformers\PostLikeCommentTransformer;
use App\Transformers\HomePostsTransformer;
use App\Transformers\PostTransformer;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;
use App\Http\Requests\HomeRequest;
use App\Http\Requests\PostDetailRequest;
use App\Models\PostBox;
use App\Helpers\HomeEsCache;
use GuzzleHttp\Client;
use \GuzzleHttp\Promise;
use Elasticsearch\ClientBuilder;
use App\Helpers\EsQueries\IsFayvedBuild;
use App\Http\Requests\LoadPostAPIRequest;
// Added by Rizwan
use App\Models\Location;
use App\Models\PostSearchAttribute;
use App\Helpers\PostCellManager;

/**
 * Post Controller
 * @author rizwan
 */
class PostController extends Controller {

    use \App\Traits\CommonTrait;
    use \App\Traits\MessageTrait;
    use \App\Traits\PostTrait;

    use \App\Traits\EntityTrait;

    protected $post;
    protected $PostTagUserTransformerm, $genericPostTransformer, $gApiDetailTransformer, $apitransformerV2;
    protected $esClient, $homeEsCache, $postService;

    public function __construct() {
        $this->postService = new \App\Services\PostService();
        $this->post = app()->make(PostRepository::class);
        $this->comment = app()->make(PostCommentRepository::class);
        $this->homePostTransFormer = app()->make(HomePostsTransformer::class);
        $this->likeCommentTransFormer = app()->make(PostLikeCommentTransformer::class);
        $this->postTransformer = app()->make(PostTransformer::class);
        $this->genericPostTransformer = app()->make(\App\Transformers\GenericPostV2Transformer::class);
        $this->gApiDetailTransformer = app()->make(\App\Transformers\GenericApiDetailTransformer::class);
        $this->attributeTransformer = app()->make(\App\Transformers\SearchAttributesTransformer::class);
        $this->topSearch = app()->make(\App\Transformers\TopSearchPostTransformer::class);
        $this->PostTagUserTransformer = app()->make(\App\Transformers\PostTagUserTransformer::class);
        $this->esClient = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
        $this->homeEsCache = HomeEsCache::getInstance($this->esClient);
        $this->apitransformerV2 = app()->make(\App\Transformers\ApiPostTransformerV2::class);
        $this->apitransformerV2->fayvedBuilder = new IsFayvedBuild();
        parent::__construct();
    }

    /**
     * Get home posts
     * @param HomeRequest $request
     * @return type
     */
    public function home(HomeRequest $request) {
        try {
            $user = $this->getCurrentUser($request);
            $inputs = $this->getInputData($request);
            $inputs["user_id"] = $user->id;
            \Log::info("------input---request--------");
            \Log::info($inputs);

            $this->saveUserCurrentLocation($user, $request->header("remote-ip"), $inputs, 'H');
            $query = PostBox::getHomePosts($user->id, $inputs['less_than'], $inputs['greater_than'], $inputs['limit']); //200
            $homePosts = $query->get();
            if (!$homePosts->isEmpty()) {
                $data = $this->homePostTransFormer->transformCollection($homePosts, $user->id, $inputs['limit']);
                return $this->setStatusCode(200)->respondWithArray(["data" => $data["response"]]);
            } else {
                $this->post->storeTemporaryLogsInDynamoDB($inputs);
                return $this->setStatusCode(400)->respondWithArray(["message" => $this->errors['noResultFound'], "error_type" => 0]);
            }
            return $this->sendCustomResponse($this->errors['generalError'], 400);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex, 400, "home");
        }
    }

    /**
     * Get home posts
     * @param HomeRequest $request
     * @return type
     */
    public function v4Home(HomeRequest $request) {
        try {
            $data = [];
            $user = $this->getCurrentUser($request);
            $inputs = $this->getInputData($request);
            $inputs["user_id"] = $user->id;
            \Log::info("------input---request--------");
            \Log::info($inputs);
            $this->saveUserCurrentLocation($user, $request->header("remote-ip"), $inputs, 'H');
            $query = PostBox::getHomePosts($user->id, $inputs['less_than'], $inputs['greater_than'], 200);
            $homePosts = $query->get();
            if (!$homePosts->isEmpty()) {
                $response = $this->homePostTransFormer->transformCollection($homePosts, $user->id, $inputs['limit']);
                $data = $response["response"];
            } else {
                $this->post->storeTemporaryLogsInDynamoDB($inputs);
            }
            return $this->setStatusCode(200)->respondWithArray(["data" => $data, 'referral' => config("general.referral_campaign")]);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex, 400, "home");
        }
    }

    /**
     * Get post detail by ID
     * @param HomeRequest $request
     * @return type
     */
    public function postDetail(PostDetailRequest $request) {
        try {
            $user = $this->getCurrentUser($request);
            $inputs = $this->getInputData($request);
            $errorResp = ["code" => 400, "error" => trans('messages.postNotFound')];
            if (!empty($inputs["fsq"])) {
                $inputs['post_id'] = $this->post->getDynamoDbObject($inputs["fsq"]);
            }
            $this->syncCampaignPostOpen($request, $inputs['post_id'], $user);
            if ($this->isNewPost($request->header('user-agent'))) {
                return $this->getNewPostDetail([$user, $inputs], $errorResp, $request);
            } else {
                return $this->getOldPostDetail([$user, $inputs], $errorResp, $request);
            }
        } catch (\Exception $ex) {
            $this->logException($ex, 'postDetail', []);
            return $this->sendExceptionError($ex);
        }
    }

    private function getOldPostDetail($object, $errorResp, $request) {
        $user = $object[0];
        $inputs = $object[1];
        if ($postBox = PostBox::getSinglePost($inputs, $user->id)) {
            $postBox->postDetail = true;
            if ($post = $this->isValidPost($postBox->post, "o")) {

                if ($this->validatePostViewPrivacy($post, $user)) {

                    $isSave = ($post->user_id != $user->id ) ? $this->saveView([$post->toArray(), $user], "P", [$request->header("remote-ip"), $request->header("device-id")], $inputs) : false;
                    $postId = $inputs['post_id'];
                    $inputs['user_id'] = $user->id;

                    $data = $this->setStatusCode(200)->respondWithItem($postBox, $this->postTransformer);
                    return $this->checkIsFayved($data->getOriginalContent(), $user->id);
                } else {
                    // Un Auhroize post detail tracking
                    $this->post->trackUnAuthorizedViews($post->id, $user->id);
                    $errorResp = ["code" => 400, "error" => trans('messages.postViewPermission')];
                }
            } else {
                $this->removeEsPost($inputs['post_id'], $request->header("token"));
                $errorResp = ["code" => 400, "error" => trans('messages.generalError')];
            }
        } else {
            $this->removeEsPost($inputs['post_id'], $request->header("token"));
        }
        return $this->sendCustomResponse($errorResp["error"], $errorResp["code"]);
    }

    /**
     * This action is deprecated
     * @param PostDetailRequest $request
     * @return type
     */
    public function getPostLikesAndComments(PostDetailRequest $request) {
        return $this->sendCustomResponse($this->errors['deprecated'], 400);
    }

    /**
     *
     * @param type $request
     * @param type $post_id
     * @return type
     */
    private function getLikes($request, $post_id) {
        try {
            $url = config('general.user_micro_service') . 'loadLikes';
            $newClient = new Client();
            $parameters = ["data" => ['post_id' => $post_id]];
            $request = $newClient->getAsync($url, ['query' => $parameters, 'headers' => ['token' => $request->header('token'), 'user-agent' => config("general.internal_service_id")]]);
            $responses = Promise\unwrap([$request]);
            if ($responses[0]->getStatusCode() == 200) {
                $resp = json_decode($responses[0]->getBody()->getContents(), true);
                return $resp['data'];
            }
            return [];
        } catch (\Exception $ex) {
            return [];
        }
    }

    public function getPostCount(Request $request) {
        $post_count = $this->post->getPostCount($request->get("user_id"));
        return $this->sendCustomResponse($post_count, 200);
    }

    /**
     * get post tag users list
     * @param Request $request
     * @return type
     */
    public function getPostTagUsers(PostDetailRequest $request) {
        try {
            $user = $this->getCurrentUser($request);
            $inputs = $request->all();
            $inputs = $this->prepareRequestInputs($this->getInputData($request), ['post_id', 'offset', 'limit', 'search_key']);
            $users = $this->post->getPostTagUsers($user->id, $inputs);

            return $this->setStatusCode(200)->respondWithCollection($users, $this->PostTagUserTransformer);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    public function getSearchPostDetail(Request $request) {
        try {
            $inputs = $request->all();

            if (!isset($inputs['id']) || !isset($inputs['type'])) {
                return $this->sendCustomResponse($this->errors['paramInfoMiss'], 400);
            }

            if ($data = \App\Models\PostSearchAttribute::getSourceBySourceIDType($inputs['type'], $inputs['id'])) {
                return $this->respondWithItem($data, $this->attributeTransformer);
            }
            return response()->json(["data" => (new \stdClass())], 200);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Get top search posts
     * @param Request $request
     * @return type
     */
    public function getTopSearchList(Request $request) {
        try {
            return $this->sendCustomResponse($this->success["deprecated"], 200);
            $inputs = $request->all();
            $limit = (isset($inputs["limit"]) && is_numeric($inputs["limit"]) && $inputs["limit"] > 0) ? $inputs["limit"] : 10;
            $posts = \App\Models\SearchPost::getTopSearchPosts($limit);
            return $this->setStatusCode(200)->respondWithCollection($posts, $this->topSearch);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Get top URL posts
     * @param Request $request
     * @return type
     */
    public function getTopUrlPostList(Request $request) {
        try {
            return $this->sendCustomResponse($this->success["deprecated"], 200);
            $inputs = $request->all();
            $limit = (isset($inputs["limit"]) && is_numeric($inputs["limit"]) && $inputs["limit"] > 0) ? $inputs["limit"] : 10;
            $posts = \App\Models\UrlPost::getTopUrlPosts($limit);
            return $this->setStatusCode(200)->respondWithCollection($posts, $this->topSearch);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Validate Campaign post
     * @param type $request
     * @param type $post_id
     * @param type $user
     * @return boolean
     */
    private function syncCampaignPostOpen($request, $post_id = 0, $user) {
        $headers = $request->header();
        if (isset($headers['campaign-id'][0]) && $headers['campaign-id'][0] > 0) {
            dispatch(new \App\Jobs\CampaignPostOpen($headers['campaign-id'][0], $post_id, $user->uid));
        }
        return true;
    }

    /**
     * Check post is_fayved or not 
     * @param type $post
     * @param type $user_id
     * @return type
     */
    private function checkIsFayved($post, $user_id, $index_key = "search_post", $is_Arr = false) {
        if ($post["data"]["type"] == 7) {
            $fayvs = $is_Arr ? $post["data"][$index_key]["fayvs"] : $post["data"]["fayvs"];
            $client = new IsFayvedBuild();
            $post["data"]["post_type"] = $post["data"]["type"];
            $post["data"]["api_attributes"]["source_id"] = $post["data"][$index_key]["source_id"];
            $post["data"]["api_attributes"]["is_fayved"] = $post["data"][$index_key]["is_fayved"];
            $post["data"]["api_attributes"]["source_type"] = $post["data"][$index_key]["source_type"];
            $post["data"]["api_attributes"]["item_type_number"] = $post["data"][$index_key]["item_type_number"];
            $post["data"]["api_attributes"]["fayvs"] = $fayvs;
            $response = $client->processIsFayved($user_id, [$post["data"]]);
            $post["data"][$index_key]["is_fayved"] = boolval($response[0]["api_attributes"]["is_fayved"]);
            $post["data"][$index_key]["fayvs"] = $post['data']['fayvs'] = $response[0]["api_attributes"]["fayvs"];
            unset($post["data"]["api_attributes"]);
//            unset($post["data"]["post_type"]);
        }
        return $is_Arr ? $post : response()->json($post);
    }

    /**
     * Latest Post detail
     * @param LoadPostAPIRequest $request
     * @return type
     */
    public function loadPostApiDetail(LoadPostAPIRequest $request) {
        try {
            $user = $this->getCurrentUser($request);
            $inputs = $this->getInputData($request);
            \Log::info("---> API Post Detail Inputs <---");
            \Log::info(print_r($inputs, true));

            $errorResp = ["code" => 400, "error" => trans('messages.postNotFound')];
            if ($inputs['type'] == "A") {
                return $this->getApiDetail($user, $inputs, $request);
            } else {
                if (!is_numeric($inputs['id'])) {
                    $inputs['post_id'] = $this->post->getDynamoDbObject($inputs['id']);
                } else {
                    $inputs['post_id'] = $inputs['id'];
                }
                $inputs['search_key'] = isset($inputs["content"]) ? $inputs["content"] : "";
                $this->syncCampaignPostOpen($request, $inputs['post_id'], $user);
                return $this->getNewPostDetail([$user, $inputs], $errorResp, $request);
            }
        } catch (\Exception $ex) {

            $this->logException($ex, 'loadPostApiDetail', []);
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Get and prepare New Design post detail
     * @param type $object
     * @param type $errorResp
     * @param type $request
     * @return type
     */
    private function getNewPostDetail($object, $errorResp, $request) {
        list($user, $inputs) = $object;
//        \DB::enableQueryLog(); // Enable query log

        $manager = new PostCellManager([$inputs["post_id"]], $user->id);
        $manager->handle();

        if ($manager->exists($inputs["post_id"])) {
            if ($manager->validatePrivacy($inputs["post_id"])) {
                $model = $manager->getModel($inputs["post_id"]);
                $view_type = $model['post_type_id'] == config("general.post_type_index.product") ? "PD" : "P";
                $isSave = ($model["user_id"] != $user->id ) ? $this->saveView([$model, $user], $view_type, [$inputs['ip'], $request->header("device-id")], $inputs) : false;
                //update short code if not exists in system
                $this->updateShortCode($model, $request->header("token"));

                $response = $this->setStatusCode(200)->respondWithItem([$model, $user, $inputs], $this->genericPostTransformer);
                return $response;
            } else {
                $errorResp = ["code" => 400, "error" => trans('messages.postViewPermission')];
            }
        }
        return $this->sendCustomResponse($errorResp["error"], $errorResp["code"]);

        /*
         * Depriciated
          $queries = \DB::getQueryLog();
          return dd($queries);
          die;
          if ($postBox = PostBox::getNewPostDetail($inputs, $user->id)) {
          if ($post = $this->isValidPost($postBox->post, "o")) {

          if ($this->validatePostViewPrivacy($post, $user)) {

          $isSave = ($post->user_id != $user->id ) ? $this->saveView([$post->toArray(), $user], "P", [$inputs['ip'], $request->header("device-id")], $inputs) : false;
          $postId = $inputs['post_id'];
          $inputs['user_id'] = $user->id;

          $this->updateShortCode($post, $request->header("token"));

          $data = $this->setStatusCode(200)->respondWithItem([$postBox, $user, $inputs], $this->genericPostTransformer);
          $data = $this->checkIsFayved($data->getOriginalContent(), $user->id, "api_post", true);


          $data = $this->prepareSchemeUrl($data, $postBox, $request);


          unset($data['data']['fayvs']);
          return response()->json($data, 200);
          } else {
          // Un Auhroize post detail tracking
          $this->post->trackUnAuthorizedViews($post->id, $user->id);
          if (isset($inputs['type']) && $post->post_type_id == config("general.post_type_index.search")) {
          //                        $api_inputs = ['source_id' => $post->source_id, 'source_type' => $post->source_type, 'item_type_number' => $post->item_type_number];
          //                        return $this->getApiDetail($user, $api_inputs, $request);
          }
          $errorResp = ["code" => 400, "error" => trans('messages.postViewPermission')];
          }
          } else {
          $this->removeEsPost($inputs['post_id'], $request->header("token"));
          }
          } else {
          $this->removeEsPost($inputs['post_id'], $request->header("token"));
          }
          return $this->sendCustomResponse($errorResp["error"], $errorResp["code"]);
         * 
         */
    }

    /**
     * This method is only use for internal edit post detail
     * @param HomeRequest $request
     * @return type
     */
    public function getInternalEditPostDetail(Request $request) {
        try {
            $user = $this->getCurrentUser($request);
            $inputs = $request->get("data");
            $inputs["ip"] = $request->header("remote-ip");
            if (config("general.base_app_version") >= 4.4) {
                $inputs['type'] = "P";
            }
            $errorResp = ["code" => 400, "error" => trans('messages.postNotFound')];
            return $this->getNewPostDetail([$user, $inputs], $errorResp, $request);
        } catch (\Exception $ex) {
            $this->logException($ex, 'getInternalEditPostDetail', []);
            return $this->sendExceptionError($ex);
        }
    }

    /**
     * Prepare API post scheme URL
     * @param type $data
     * @param type $postBox
     * @param type $request
     * @return string
     */
    private function prepareSchemeUrl($data, $postBox, $request) {
        if ($postBox->post->post_type_id == config('general.post_type_index.search')) {
            $data["data"]["api_post"]["scheme_url"] = $source_link = "";
            $source_type = $postBox->post->source_type;
            if ($source_type == "imdb") {
                $imdb_id = $postBox->post->postable['source_id'];
                if (!empty($postBox->post->postable['session_id'])) {
                    $imdb_id = $postBox->post->postable['session_id'];
                    $source_link = str_replace(array("http://", "https://"), "", "{$imdb_id}");
                }
            } else if ($source_type == "youtube") {
                $youtube_source = config('g_map.youtube_site_base_url') . $postBox->post->postable['source_id'];
                $source_link = strip_tags(str_replace(array("http://", "https://"), "", $youtube_source));
            } else if ($source_type == "anghami") {
                $source_link = $postBox->post->postable['source_id'];
            } else {
                $source_link = !empty($postBox->post->postable['external_url']) ? strip_tags(str_replace(array("http://", "https://"), "", $postBox->post->postable['external_url'])) : "";
            }
            if (!empty($source_link)) {
                $device = $this->getRequestDeivce($request->header("user-agent"));
                $data["data"]["api_post"]["scheme_url"] = config('g_map.scheme_url.' . $device . ".{$data["data"]["api_post"]["source_type"]}") . $source_link;
            }
        }
        return $data;
    }

    private function getApiDetail($user, $inputs = [], $request = null) {
        $client = new \App\Helpers\GuzzleHelper();
        $api_object = [];
        $device_type = $this->getRequestDeivce($request->header("user-agent"));
        if ($api = (object) $this->getAPIObject($inputs["id"], $inputs["api"])) {
            $data = $this->setStatusCode(200)->respondWithItem(["a" => $api, "u" => $user, "d" => $device_type, 'inputs' => $inputs], $this->apitransformerV2);
            $source_key = "{$inputs["api"]['source_type']}-{$inputs["api"]['source_id']}@{$inputs["api"]['item_type_number']}";
            $this->saveView([$data->getOriginalContent()['data'], $user], "A", [$request->header("remote-ip"), $request->header("device-id")], $inputs, $source_key);
            \Log::info(print_r($data->getOriginalContent(), true));
            return $data;
        } else if ($data = $client->getApiPostRelatedData(0, $inputs["api"]['source_type'], $inputs["api"]['source_id'], $inputs["api"]['item_type_number'], $request->header('remote-ip'))) {
            $data['model']['device_type'] = $device_type;
            $source_key = "{$data['model']['source_type']}-{$data['model']['source_id']}@{$data['model']['item_type_number']}";
            $api_object = ($data['model']['source_type'] == config("general.post_source_types.google")) ? Location::getLocationByCol('source_key', $source_key) : PostSearchAttribute::getAPIByCol('source_key', $source_key);
            \Log::info("---> API Post Detail Attr <---");
            \Log::info(print_r($data, true));
            $data = $this->setStatusCode(200)->respondWithItem([$data['model'], $api_object], $this->gApiDetailTransformer);
            $data = $this->checkIsFayved($data->getOriginalContent(), $user->id, "api_post", true);
            $data['data']['post_type'] = config('general.post_type_index.search');
            unset($data['data']['fayvs']);
            $this->saveView([$data['data'], $user], "A", [$request->header("remote-ip"), $request->header("device-id")], $inputs, $source_key);
            return response()->json($data, 200);
        } else {
            return $this->sendCustomResponse(trans('messages.postNotFound'), 400);
        }
    }

}
