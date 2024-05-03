<?php

namespace App\Repositories;

use App\Repositories\Contracts\PostCommentRepository;
use Carbon\Carbon;
use App\Models\CommentMentionUser;
use App\Models\User;
use App\Helpers\ElasticSearchHelper;

/**
 * Description of EloquentPostComment
 * @author rizwan
 */
class EloquentPostComment extends AbstractEloquentRepository implements PostCommentRepository {

    private $mentions = [];
    private $clearCacheUsers = [];

    use \App\Traits\ClearCache;
    use \App\Traits\AWSTrait;
    use \App\Traits\RedisTrait;

    /**
     * save comment
     * @param array $data
     * @return type
     */
    public function saveComent(array $data, $device_id = "") {
        $inputs = $data;
        $data['user_post_id'] = $data['post_id'];
        $data['created_at'] = Carbon::now();
        unset($data['post_id']);
        if ($data = $this->validateCommentMentions($data)) {
            $data = $this->processPostCommentLocation($data, $inputs, $device_id);

            if ($comment = $this->save($data)) {
                $this->deleteCommentCache($data["user_post_id"]);
                return $this->processToSaveMentionUsers($comment, $data);
            }
        }
        return false;
    }

    private function processToSaveMentionUsers($comment, $data) {
        if (isset($data['mention']) && !empty($data['mention'])) {
            if ($this->saveCommentMentionUsers($comment, $data['mention'], $data["post_owner"])) {
                
            }
        }
        return $this->findOneWithRelations(['id' => $comment->id], ['user', 'commentMention']);
    }

    /**
     * load more comments
     * @param type $data
     * @return type
     */
    public function loadMoreComments($data, $isCount = true, $isFirst = false) {
        $limit = (isset($data["limit"]) && $data["limit"] != 0 ) ? $data["limit"] : 40;
        $comments = $this->model->where(function ($sql) use ($data) {
                    $sql->where('user_post_id', '=', $data['post_id']);
                    if (isset($data['type']) && $data['type'] == 'up') {
                        $sql->where('id', '<', $data['last_id']);
                    } elseif (isset($data['type']) && $data['type'] == 'down') {
                        $sql->where('id', '>', $data['last_id']);
                    }
                })->with(['user'])->limit($limit)->orderBy("id", "DESC")->get();
        $comments = $comments->reverse();
        $comments_count = $isCount ? $this->model->comment_count($data["post_id"]) : 0;
        $total_likes = $isCount ? \App\Models\Like::countPostLikes($data["post_id"]) : 0;
        return ["comments" => $comments, "total_comments" => $comments_count, "total_likes" => $total_likes];
    }

    /**
     * prepare and save comment mention users
     * @param type $comment
     * @param type $mentions
     * @param type $post_owner
     * @return boolean
     */
    public function saveCommentMentionUsers($comment, $mentions = [], $post_owner = 0) {
        $mentionArr = [];
        foreach ($mentions as $key => $mention) {
            $mentionArr[$key]['user_id'] = $mention;
            $mentionArr[$key]['post_comments_id'] = $comment->id;
            $mentionArr[$key]['user_posts_id'] = $comment->user_post_id;
            $mentionArr[$key]['comment_owner'] = $comment->user_id;
            $mentionArr[$key]['post_owner'] = $post_owner;
        }
        if (!empty($mentionArr)) {
            return CommentMentionUser::saveMentions($mentionArr);
        }
        return true;
    }

    /**
     * get comment by id with relashion
     * @param type $id
     * @return type
     */
    public function getCommentById($id) {
        return $this->findOneWithRelations(['id' => $id], ['post']);
    }

    /**
     * 
     * @param type $comment
     * @return type
     */
    public function archiveComment($comment) {

        if (isset($comment->post)) {
            $this->clearCacheUsers[] = $comment->post->user_id;
            unset($comment->post);
        }
        return \DB::transaction(function () use ($comment) {


                    if ($comment->delete()) {
                        \App\Models\FayvoActivity::deleteActivitiesByComment($comment->id);

                        $this->clearYouCache($this->clearCacheUsers);
                        $this->deleteCommentCache($comment->post->id);
                        return true;
                    }
                    if ($this->commentMentionUsersArchived($comment)) {
                        
                    }
                }, 5);
    }

    /**
     * NOT IN USE
     * @param $comment
     * @return \Aws\Result
     */
    private function archiveCommentToDynamoDb($comment) {

        return true;
        /*
        $dynamoDB = $this->buildDynamoDBClient();
        $marshaler = $this->getMarshaler();

        $data = array_filter($comment->toArray());

        $data['envoirnment'] = env('APP_ENV', 'staging');
        $data['deleted_at'] = Carbon::now()->format("Y-m-d\TH:i:s\Z");

        if ($mentions = CommentMentionUser::getCommentMentionsByComment($comment->id)) {
            $this->commentMentionUsersArchived($comment, $mentions);
            $data["mentions"] = $mentions;
        }
        array_walk_recursive($data, function (& $item) {
            if ('' === $item) {
                $item = null;
            }
        });

        $item = $marshaler->marshalJson(json_encode($data));
        $params = [
            'TableName' => config("cognito.dynamo_db_archive_comment"),
            'Item' => $item
        ];
        return $dynamoDB->putItem($params);
        */
    }

    /**
     * delete and move to archived comment mention users
     * @param type $mentions
     * @return boolean
     */
    public function commentMentionUsersArchived($comment, $mentions = []) {
        $this->clearCacheUsers = array_merge($this->clearCacheUsers, array_column($mentions, 'user_id'));
        if (CommentMentionUser::deleteCommentMentionsByComment($comment->id)) {
            return true;
        }
    }

    /**
     * validate post comment mention users
     * @param type $comment
     * @return boolean
     */
    private function validateCommentMentions($comment) {
        $mentions = $this->extractMentionsUsers($comment);
        $mentions = $this->validateMentionUsername($mentions);
        if (isset($mentions) && !empty($mentions)) {
            if ($users = User::getUsersByUsername($mentions)) {
                $comment = $this->processMentionsValidation($users, $comment, $mentions);
            }
        }
        return $comment;
    }

    public function validateMentionUsername($mentions = []) {
        $response = [];
        if (!empty($mentions)) {
            foreach ($mentions as $key => $mention) {
                if (!$this->isEmojoExists($mention)) {
                    $response[] = $mention;
                }
            }
        }
        return $response;
    }

    /**
     * Process comment mention users validation
     * @param type $users
     * @param type $comment
     * @param type $mentions
     * @return type
     */
    private function processMentionsValidation($users, $comment, $mentions) {
        foreach ($mentions as $key => $mention) {
            $comment['mention'][$key] = $users[array_search($mention, array_column($users, 'username'))]['id'];
        }
        if (!empty($comment['mention'])) {
            $comment['mention'] = $output = array_slice(array_unique($comment['mention']), 0, 20);
        }
        return $comment;
    }

    /**
     * extract comment mention users
     * @param type $comment
     * @return type
     */
    private function extractMentionsUsers($comment) {
        $mentions = [];
        if (strpos($comment['comment'], '@') !== false) {
            $mentions = explode('@', $comment['comment']);
            if (!empty($mentions)) {
                unset($mentions[0]);
                $mentions = $this->processMentionExtraction($mentions, $comment);
            }
        }
        return $mentions;
    }

    /**
     * Process mention user extraction
     * @param type $mentions
     * @param array $comment
     * @return type
     */
    private function processMentionExtraction($mentions, $comment) {
        $mentions = array_values($mentions);
        foreach ($mentions as $key => $mention) {
            $mentions[$key] = strtolower(trim(explode(' ', $mention)[0]));
        }
        $comment['mention'] = array_filter($mentions, function ($value) {
            return $value !== '';
        });
        return $mentions;
    }

    /**
     * Load comment mention people list
     * @param type $user_id
     * @param array $data
     * @return type
     */
    public function getCommentPeopleList($user_id, $data) {
        $data['user_id'] = $user_id;
        $helperClient = new ElasticSearchHelper();
        if ($users = $helperClient->getCommentPeopleList($data)) {
            return $users;
        }
        return [];
    }

    /**
     * Check emoji from string
     * @return bool if existed emoji in string
     */
    public function isEmojoExists($str) {
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        preg_match($regexEmoticons, $str, $matches_emo);
        if (!empty($matches_emo[0])) {
            return true;
        }

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        preg_match($regexSymbols, $str, $matches_sym);
        if (!empty($matches_sym[0])) {
            return true;
        }

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        preg_match($regexTransport, $str, $matches_trans);
        if (!empty($matches_trans[0])) {
            return true;
        }

        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        preg_match($regexMisc, $str, $matches_misc);
        if (!empty($matches_misc[0])) {
            return true;
        }

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        preg_match($regexDingbats, $str, $matches_bats);
        if (!empty($matches_bats[0])) {
            return true;
        }

        return false;
    }

    /**
     * Process Post Comment Location
     * @param type $respons
     * @param type $inputs
     * @return string
     */
    private function processPostCommentLocation($respons = [], $inputs = [], $device_id = "") {
        $respons["service"] = (isset($inputs["service"]) && !empty($inputs["service"])) ? $inputs["service"] : NULL;
        $respons["is_ip_location"] = true;
        $ipInfo = (new \App\Helpers\GuzzleHelper())->getIpLocation($inputs["client_ip_address"]);
        $respons["country_id"] = isset($ipInfo["country_id"]) ? $ipInfo["country_id"] : 0;
        if ($location = $this->getUserLastLocation($inputs["user_id"])) {
            \Log::info("-- User redis location founded for comment--");
            $respons = $this->prepareCommenLoArr($respons, $location["location"], false);
        } else if (!empty($device_id) && $location = $this->getDeviceLastLocation($device_id)) {
            \Log::info("-- Device redis location founded for comment--");
            $respons = $this->prepareCommenLoArr($respons, $location["location"], false);
        } else if (!empty($ipInfo)) {
            \Log::info("-- Ip location founded and will be user for comment --");
            $ipInfo['lon'] = isset($ipInfo['lng']) ? $ipInfo['lng'] : $ipInfo['longitude'];
            $ipInfo['lat'] = isset($ipInfo['lat']) ? $ipInfo['lat'] : $ipInfo['latitude'];
            $respons = $this->prepareCommenLoArr($respons, $ipInfo, true);
        }
        return $respons;
    }

    /**
     * Prepare comment save location array
     * @param type $respons
     * @param type $location
     * @param type $isIp
     * @return string
     */
    private function prepareCommenLoArr($respons, $location = [], $isIp = false) {
        $respons["is_ip_location"] = $isIp;
        $respons["lat"] = isset($location["lat"]) ? $location["lat"] : $location["latitude"];
        $respons["lon"] = isset($location["lon"]) ? $location["lon"] : $location["longitude"];
        $respons["geo_location"] = round($respons["lat"], 2) . "_" . round($respons["lon"], 2);
        return $respons;
    }

}
