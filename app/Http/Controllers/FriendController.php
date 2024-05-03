<?php

namespace App\Http\Controllers;

use App\Helpers\EsQueries\FriendPost;
use App\Transformers\FriendPostTransformer;
use App\Models\UserPost;
use App\Http\Requests\FriendPostRequest;
use App\Helpers\EsQueries\IsFayvedBuild;
use App\Models\ApiSession;
use Carbon\Carbon;
Use Illuminate\Http\Request;

/**
 * Description of FriendController
 *
 * @author rizwan
 */
class FriendController extends Controller {

    use \App\Traits\CommonTrait;
    use \App\Traits\RedisTrait;
    use \App\Traits\UserInterestsTrait;

    private $friendPostTransformer = null;
    private $fayved = null;

    public function __construct(IsFayvedBuild $fayved) {

        $this->friendPostTransformer = app()->make(FriendPostTransformer::class);
        $this->fayved = $fayved;
        parent::__construct();
    }

    /**
     * Generate home session
     * @param type $user
     * @param type $inputs
     * @param type $ip_address
     * @return type
     */
    private function generateSessionKey($user, $inputs, $ip_address = null) {
        $cacheKey = config("general.redis_keys.user_home_session") . $user->id;
        $session = [
            'key' => 'H' . mt_rand(1, 9999) . time() . $user->id,
            'timestamp' => Carbon::parse(Carbon::now())->format("Y-m-d\TH:i:s\Z"),
            'ip_address' => $ip_address, 'api_service' => 'H'
        ];
        $session['lat'] = isset($inputs["lat"]) ? $inputs["lat"] : NULL;
        $session['lon'] = isset($inputs["lon"]) ? $inputs["lon"] : NULL;
        $session["api_offset"] = $inputs["offset"];
        // Category id column is use for storing the use top trend post for signle life session
        $session["tt"] = (new FriendPost($user->id, $session))->getTopTrendingPost($user->id);
        ApiSession::saveUserHomeSession($session, $user->id);
        $session["limit"] = $inputs["limit"];
        $session["greater_than"] = $inputs["greater_than"];
        $this->cacheArrayInKey($cacheKey, $session);
        return $session;
    }

    /**
     * Get trending session key
     * @param type $user
     * @param type $inputs
     * @param type $ip_address
     * @return type
     * @throws \Exception
     */
    private function getSessionKey($user, $inputs, $ip_address = null) {
        $cacheKey = config("general.redis_keys.user_home_session") . $user->id;
        if (!$data = $this->getCacheArrayByKey($cacheKey)) {
            if ($data = ApiSession::getUserLastHomeSession($user->id)) {
                $data = [
                    'key' => $data->key, 'timestamp' => Carbon::parse($data->timestamp)->format("Y-m-d\TH:i:s\Z"),
                    'ip_address' => $data->ip_address, 'lat' => $data->lat, 'lon' => $data->lon,
                    'api_service' => $data->api_service,
                    'tt' => $data->tt
                ];
                $this->cacheArrayInKey($cacheKey, $data);
            } else {
                throw new \Exception(trans("messages.homeSessionKeyExp"), 501);
            }
        }
        $data["greater_than"] = $inputs["greater_than"];
        $data["api_offset"] = $inputs["offset"];
        $data["limit"] = $inputs["limit"];
        return $data;
    }

    /**
     * Fetch home session
     * @param type $user
     * @param type $inputs
     * @param type $ip_address
     * @return type
     */
    private function fetchTrendingSessionKey($user, $inputs, $ip_address = "") {
        if ($inputs['offset'] <= 0) {
            return $this->generateSessionKey($user, $inputs, $ip_address);
        } else {
            return $this->getSessionKey($user, $inputs, $ip_address);
        }
    }

    /**
     * Fetch friends posts
     * @param FriendPostRequest $request
     * @return type
     */
    public function getFriendsPosts(FriendPostRequest $request) {
        try {
            $ip_address = $request->header('remote-ip');
            $user = $this->getCurrentUser($request);
            $inputs = $this->getInputData($request);
            $this->saveUserCurrentLocation($user, $ip_address, $inputs, 'H', $request->header("device-id"));
            $session_inputs = $this->fetchTrendingSessionKey($user, $inputs, $ip_address);
            return $this->processFriendsPosts($user, $session_inputs, $ip_address);
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex, 400, "getFriendsPosts");
        }
    }

    /**
     * Process Friends posts
     * @param type $user
     * @param type $inputs
     * @param type $ip_address
     * @return type
     */
    private function processFriendsPosts($user, $inputs, $ip_address) {
        $response = [];
        $friendEs = new FriendPost($user->id, $inputs);
        if ($posts = $friendEs->getFriendsPosts($this->getUserInterestsPair($user->id))) {
            $posts = $this->setStatusCode(200)->respondWithCollection($this->shiftEsToSingleArray($posts), $this->friendPostTransformer);
            $response = $this->manipulateTrendingArray($friendEs, $user->id, $posts->getOriginalContent()['data']);
        }
        return $this->setStatusCode(200)->respondWithArray(['data' => $response]);
    }

    private function shiftEsToSingleArray($posts, $isBookmarkerd = false) {
        $response = [];
        if ($isBookmarkerd) {
            foreach ($posts as $key => $post) {
                $response[$key]['post'] = $post['_source'];
                $response[$key]['user'] = ($post['inner_hits']['friend_user']['hits']['total'] > 0) ? $post['inner_hits']['friend_user']['hits']['hits'][0]["_source"] : (($post['inner_hits']['owner_user']['hits']['total'] > 0) ? $post['inner_hits']['owner_user']['hits']['hits'][0]["_source"] : $post['inner_hits']['public_user']['hits']['hits'][0]["_source"]);
            }
        } else {
            foreach ($posts as $key => $post) {
                $response[$key]['post'] = $post['_source'];
                $response[$key]['user'] = $post['inner_hits']['user']['hits']['hits'][0]['_source'];
            }
        }
        return $response;
    }

    /**
     * Manipulate trending posts
     * @param type $posts
     * @return type
     */
    private function manipulateTrendingArray($friendEs, $user_id, $posts) {
        $posts = $this->shiftArrayToKeyPair($posts, 'id');
        $posts = $this->fayved->processIsFayved($user_id, $posts);
//        $postIds = $this->filterPostIdsByType($posts);
        $likesComments = UserPost::getPostsTotalLikesComments(array_keys($posts), $user_id);
        $posts = $friendEs->mergerLIkesCommentsIntrending($posts, $likesComments);
        return array_values($posts);
    }

    public function getUserBookmarkedPosts(Request $request) {
        try {
            $inputs = $request->get('data');
            $response = [];
            $inputs["timestamp"] = Carbon::now();
            $friendEs = new FriendPost($inputs["user_id"], $inputs);
            if ($posts = $friendEs->getBookmarkedPostsByids()) {
                $posts = $this->setStatusCode(200)->respondWithCollection($this->shiftEsToSingleArray($posts, true), $this->friendPostTransformer);
                $response = $this->manipulateTrendingArray($friendEs, $inputs["user_id"], $posts->getOriginalContent()['data']);
            }
            return $this->setStatusCode(200)->respondWithArray(['data' => $response]);
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return response()->json(["data" => []], 200);
        }
    }

}
