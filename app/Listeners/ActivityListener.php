<?php

namespace App\Listeners;

use App\Events\ExampleEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\ActivityEvent;
use App\Models\UserPost;
use Carbon\Carbon;

class ActivityListener {

    use \App\Traits\ClearCache;
    use \App\Traits\AWSTrait;

    private $activity;
    private $thumb;
    private $cacheClearUsers = [];
    protected $dynamoDb = 'ProductionActivityTrigger';
    protected $isMentionAllowed = false;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        $this->dynamoDb = config("cognito.activity_dynamodb_table");
    }

    /**
     * Handle the event.
     *
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(ActivityEvent $event) {
        try {
            $data = $event->model;
            $event->activityUser->thumb = $event->activityUser->setAppends(['thumb']);
            $type = strtolower($event->type);
            switch ($type) {
                case 'post_comment':
                    $this->createPostCommentActivity($data->toArray(), 'C', $event->activityUser);
                    break;
                default:
                    break;
            }
            return true;
        } catch (\Exception $ex) {
            return $this->logExceptionDynamo($ex, 'CommentActivtityListener', env("APP_ENV", "local"), $event->model->toArray());
        }
    }

    /**
     * create post comment activity
     * @param type $data
     * @param type $domain
     * @return boolean
     */
    private function createPostCommentActivity($data, $domain = 'C', $activityUser) {
        $mentionUsers = [];
        // Temporarily close comment activity for influencers
        if (in_array($data['post_owner'], [96459, 96448, 96453, 96454, 96457, 96452, 96456, 96458, 96463, 96450, 97820])) {
            \Log::info("-- influencers activity temporarily close ---{$activityUser->username}");
            return true;
        }
        if ($post = $this->getPostUser($data['user_post_id'])) {
            $username = $activityUser->username;
            $this->isMentionActivityAllowed($post->postBoxesPivot->toArray());
            $activity = $this->getActivityUsers($data['user_id'], $post->user_id, $domain, $post->id);
            $activity['relation_type'] = $post->post_type_id;
            $activity['comment_text'] = $data['comment'];
            $activity['object_id'] = $data['id'];
            $activity = $this->getPostMedia($activity, $post);
            $isInsert = ($activity['related_to'] == $activityUser->id) ? false : true;
            $this->cacheClearUsers[] = $activity['related_to'];
            if ($result = $this->saveActivity($activity, $isInsert)) {

                $mentionUsers = $this->processMentionUsers($data, $mentionUsers, $post, $activityUser->id);
            }
            $activity['thumb'] = $this->thumb;
            $this->clearCache();
            $this->startProcessToSendNotification($activity, $activityUser, $post, $mentionUsers);
        }
        return true;
    }

    /**
     * Process mention users if exists
     * @param type $data
     * @param type $mentionUsers
     * @param type $post
     * @return type
     */
    private function processMentionUsers($data, $mentionUsers, $post, $login_id = 0) {
        if ($this->isMentionAllowed && isset($data['comment_mention']) && !empty($data['comment_mention'])) {
            $mentionUsers = array_merge($mentionUsers, array_column($data['comment_mention'], 'user_id'));
            $this->createCommentMentionActivity($data, $post, $login_id);
        } else {
            $mentionUsers = [];
        }
        return $mentionUsers;
    }

    /**
     * create comment mention user activities
     * @param type $data
     * @param type $post
     * @return type
     */
    private function createCommentMentionActivity($data, $post, $login_id = 0) {
        $index = 0;
        $activities = [];
        foreach ($data['comment_mention'] as $mention) {
            if ($mention['user_id'] != $post->user_id && $mention['user_id'] != $login_id) {
                $this->activity[$index] = $activities[$index] = $this->getActivityUsers($data['user_id'], $mention['user_id'], 'M', $post->id);
                $this->activity[$index] = $activities[$index]['comment_text'] = $data['comment'];
                $this->activity[$index] = $activities[$index]['object_id'] = $data['id'];
                $this->activity[$index] = $activities[$index]['relation_type'] = $post->post_type_id;
                $this->activity[$index] = $activities[$index] = $this->getPostMedia($activities[$index], $post);
                $this->activity[$index]['thumb'] = $this->thumb;
                $this->cacheClearUsers[] = $mention['user_id'];
                $index++;
            }
        }
        if ($response = $this->saveActivity($activities, false, true)) {
            foreach ($response as $key => $value)
                $response[$key] = array_merge($value, ['environment' => env("APP_ENV", "staging")]);
        }
        return [];
    }

    /**
     * Get post with media
     * @param type $post_id
     * @return type
     */
    private function getPostUser($post_id) {
        $post = UserPost::getPostById($post_id);
        if (!empty($post)) {
            return $post;
        }
        return [];
    }

    /**
     * get activity users
     * @param type $by_user
     * @param type $to_user
     * @param type $domain
     * @param type $post_id
     * @return type
     */
    private function getActivityUsers($by_user, $to_user, $domain, $post_id) {
        $response['user_id'] = $by_user;
        $response['related_to'] = $to_user;
        $response['domain'] = $domain;
        $response['status'] = 'U';
        $response['relation_id'] = $post_id;
        $response['created_at'] = Carbon::now();
        return $response;
    }

    /**
     * prepare activity media
     * @param type $activity
     * @param type $post
     * @return type
     */
    private function getPostMedia($activity, $post) {
        switch ($post->post_type_id) {
            case config('general.post_type_index.search'):
                $activity = $this->getSearchPostMedia($activity, $post->postable);
                break;
            default:
                $activity = $this->getMediaPost($activity, $post->postable);
                break;
        }
        return $activity;
    }

    /**
     * Prepare media post media
     * @param type $activity
     * @param type $post
     * @return type
     */
    private function getMediaPost($activity, $post) {
        $activity['media'] = $post->postMedia[0]['file'];
        $activity['bg_color'] = !empty($post->postMedia[0]['bg_color']) ? $post->postMedia[0]['bg_color'] : NULL;
        $activity['media_type'] = $this->getMediaType($post->postMedia[0]['file_type']);
        $activity['file_width'] = !empty($post->postMedia[0]['file_width']) ? $post->postMedia[0]['file_width'] : 0;
        $activity['file_height'] = !empty($post->postMedia[0]['file_height']) ? $post->postMedia[0]['file_height'] : 0;
        $activity['medium_file_width'] = !empty($post->postMedia[0]['medium_file_width']) ? $post->postMedia[0]['medium_file_width'] : 0;
        $activity['medium_file_height'] = !empty($post->postMedia[0]['medium_file_height']) ? $post->postMedia[0]['medium_file_height'] : 0;
        $activity['medium_file_height'] = !empty($post->postMedia[0]['medium_file_height']) ? $post->postMedia[0]['medium_file_height'] : 0;
        $activity['notification_file_width'] = !empty($post->postMedia[0]['notification_file_width']) ? $post->postMedia[0]['notification_file_width'] : 0;
        $activity['notification_file_height'] = !empty($post->postMedia[0]['notification_file_height']) ? $post->postMedia[0]['notification_file_height'] : 0;
        $activity['bucket'] = !empty($post->postMedia[0]['bucket']) ? $post->postMedia[0]['bucket'] : NULL;
        $this->thumb = !empty($post->postMedia[0]['thumb']) ? $post->postMedia[0]['thumb'] : "";
        return $activity;
    }

    /**
     * Prepare search post array for activitiess
     * @param type $activity
     * @param type $post
     * @return string
     */
    private function getSearchPostMedia($activity, $post) {
        $thumb_info = $this->getApisPostThumb($post);
        $activity['item_type'] = strtolower($post->item_type);
        $activity['item_type_number'] = $post->item_type_number;
        $activity['source_type'] = $post->source_type;
        $activity['thumbnail'] = $thumb_info['thumb'];
        $activity['bg_image'] = $post->bg_image;
        if ($post->source_type == config("general.post_source_types.google") && $location = $post->location) {
            $activity['caption'] = !empty($location->location_type) ? $location->location_type : NULL;
        }
        $activity['media'] = $thumb_info['thumb'];
        $activity['bg_color'] = $thumb_info['bg_color'];
        $activity['file_width'] = $activity['medium_file_width'] = $activity['notification_file_width'] = $thumb_info['file_width'];
        $activity['file_height'] = $activity['medium_file_height'] = $activity['notification_file_height'] = $thumb_info['file_height'];

        $activity['bucket'] = env("AWS_BUCKET", "fayvo-sample");
        $activity['media_type'] = $this->getMediaType('picture');
//        $this->thumb = $activity['thumb'] = str_replace("/original/", '/thumb/', $post->postable->thumbnail);
        $this->thumb = str_replace("/original/", '/thumb/', $post->postable->thumbnail);
        return $activity;
    }

    private function getApisPostThumb($post) {
        $media = $post->toArray();
        if ($post->source_type == "google" && !$post->postable->thumb_status) {
            $lat_lon = ["lat" => $post->postable->latitude, $post->postable->longitude];
            $obj = new \App\Transformers\BaseTransformer();
            return [
                'thumb' => $obj->prepareDiscoverGoogleMapUrl($post->postable->toArray(), $lat_lon),
                "bg_color" => config("g_map.map_default_bg_color"),
                "file_width" => config("g_map.map_dimensions.size_400_600.width"),
                "file_height" => config("g_map.map_dimensions.size_400_600.height")
            ];
        } else {
            $dimensions = $this->getAPIPostDimensions($post->toArray());
            $bg_color = (isset($media['post_media']) && !empty($media['post_media'])) ? $media['post_media'][0]['bg_color'] : str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT);
            return ['thumb' => $post->postable->thumbnail, "bg_color" => $bg_color, "file_width" => $dimensions["width"], "file_height" => $dimensions["height"]];
        }
    }

    /**
     * Get Api Post thumb dimensions
     * @param type $post
     * @return type
     */
    private function getAPIPostDimensions($post) {
        $dimensions = ["width" => 0, "height" => 0];
        if (isset($post['postable']) && !empty($post['postable']['width']) && $post['postable']['width'] > 0) {
            $dimensions = ["width" => $post['postable']['width'], "height" => $post['postable']['height']];
        } elseif (isset($post['post_media']) && !empty($post['post_media'])) {
            $dimensions = ["width" => $post['post_media'][0]['file_width'], "height" => $post['post_media'][0]['file_height']];
        } else {
            list($width, $heigh) = getimagesize($post['postable']['thumbnail']);
            $dimensions = ["width" => $width, "height" => $heigh];
        }
        return $dimensions;
    }

    /**
     * get media type
     * @param type $type
     * @return type
     */
    private function getMediaType($type) {
        return strtoupper($type[0]);
    }

    /**
     * save new activity
     * @param type $activity
     * @return boolean
     */
    private function saveActivity($activity, $isInsert = true, $isMention = false) {

        if (!empty($activity) && $isInsert) {
            $result = \App\Models\FayvoActivity::create($activity);
            return $result;
        } else {
            if ($isMention && \App\Models\FayvoActivity::insert($activity)) {
                $last_id = \DB::getPdo()->lastInsertId();
                foreach ($activity as $key => $value) {
                    if ($key == 0) {
                        $activity[$key]['id'] = (int) $last_id;
                    } else {
                        $activity[$key]['id'] = (int) $last_id + $key;
                    }
                    $activity[$key]['thumb'] = $this->thumb;
                }
            }
        }
        return $activity;
    }

    /**
     * Start process to send push notifications
     * @param type $activity
     * @param type $activityUser
     * @param type $post
     * @param type $mentionUsers.
     */
    private function startProcessToSendNotification($activity, $activityUser, $post, $mentionUsers) {
        $receiver = ($activity['related_to'] != $activityUser->id) ? \App\Models\DeviceToken::getDeviceTokensByUsers([$post->user_id], $activityUser->id, $activity['domain']) : [];
        if (!empty($receiver) && !empty($activity)) {
            $job = ['activity' => $activity, 'receiver' => $receiver[0]->toArray(), 'user' => $activityUser->toArray()];
            dispatch(new \App\Jobs\SendPushNotification($job));
        }
        $receivers = \App\Models\DeviceToken::getDeviceTokensByUsers($mentionUsers, $activityUser->id, 'M');
        if (!empty($receivers) && !empty($this->activity)) {
            $job = ['activity' => $this->activity, 'receiver' => $receivers->toArray(), 'user' => $activityUser->toArray(), 'is_mention' => true];
            dispatch(new \App\Jobs\SendPushNotification($job));
        }

        return true;
    }

    /**
     * Clear you activities cache of related users
     * @return boolean
     */
    public function clearCache() {
        if (!empty($this->cacheClearUsers)) {
            $this->clearYouCache($this->cacheClearUsers);
        }
        return true;
    }

    /**
     * prepare DynamoDB array
     * @param type $data
     * @param type $result
     * @return type
     */
    private function prepareDynamoActivityArr($data, $result) {
        if (!empty($data) && !empty($result)) {
            $data['environment'] = env("APP_ENV", "staging");
            $data['id'] = (int) $result->id;
            $data['thumb'] = $this->thumb;
        }
        return $data;
    }

    /**
     * Save activities to dynamo DB
     * @param type $data
     * @return boolean
     */
    private function saveToDynamoDb($data, $isMention = false) {
        return true;
        /*
          $response = true;
          $marshaler = $this->getMarshaler();
          $client = $this->buildDynamoDBClient();
          $data = array_filter($data);
          if ($isMention) {
          $requests = [];
          foreach ($data as $key => $value) {
          $item = $this->getMarshaler()->marshalJson(json_encode(array_filter($value)));
          $requests['RequestItems'][$this->dynamoDb][] = ["PutRequest" => ["Item" => $item]];
          }
          return $client->batchWriteItem($requests);
          }
          if ($data['user_id'] != $data['related_to']) {
          $params = [
          'TableName' => $this->dynamoDb,
          'Item' => $marshaler->marshalJson(json_encode($data))
          ];
          $response = $client->putItem($params);
          }
          return $response;
         */
    }

    /**
     * Check mention user activity allowed or not
     * @param type $boxes
     */
    public function isMentionActivityAllowed($boxes = []) {
        if (!empty($boxes)) {
            $stack = array_column($boxes, 'status');
            if (in_array('A', $stack)) {
                $this->isMentionAllowed = true;
            }
        }
        return $this->isMentionAllowed;
    }

}
