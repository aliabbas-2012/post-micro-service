<?php

namespace App\Helpers;

/**
 * All methods related to push notification
 * will be here
 */
use Carbon\Carbon;
use App\Models\FayvoActivity;

class NotificationProcess {

    private $activity;
    private $selectedUsers = [];

    public function __construct() {
        
    }

    /**
     * Get development envoirnment for selected users
     * ON production server
     * @param type $user_id
     * @param type $currentEnv
     * @return type
     */
    private function getDevEnvoirnment($user_id = 0, $currentEnv = 'production') {
        $response = in_array($user_id, $this->selectedUsers) ? "development" : $currentEnv;
        return $response;
    }

    /**
     * Print / Log exception
     * @param type $ex
     * @param type $method
     */
    public function printException($ex, $method = 'undefined') {
        $exception['Method'] = $method;
        $exception['Message'] = $ex->getMessage();
        $exception['File'] = $ex->getFile();
        $exception['Line'] = $ex->getLine();
        \Log::info($exception);
    }

    /**
     * Parse carbon datetime
     * @param type $created_at
     * @return type
     */
    public function parseCarbon($created_at) {
        return Carbon::parse($created_at)->format("Y-m-d\TH:i:s\Z");
    }

    /**
     * send push notifications
     * @param type $data
     * @param type $receiver
     * @param type $sender
     * @return boolean
     */
    public function sendPushNotification($data, $receiver, $sender = []) {
        if (!empty($data) && !empty($receiver['device']) && !empty($sender)) {
            $tokens = $this->prepareTokens([$receiver['device']]);
            $data = $this->preparePushNotificationData($data, $sender, $receiver);
            $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $data, $this->getDevEnvoirnment($receiver['id'], $receiver['device']['envoirement'])) : false;
            $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $data) : false;
            return true;
        }
        return true;
    }

    /**
     * send post comment push notification
     * @param type $data
     * @param type $receiver
     * @param type $sender
     * @return boolean
     */
    public function sendCommentPushNotification($data, $receiver, $sender = []) {
        try {
            if (!empty($data) && !empty($receiver) && !empty($sender)) {
                $tokens = $this->prepareTokens([$receiver]);
                $data = $this->prepareCommentData($data, $sender, $receiver);
                \Log::info("==== push notification data ====");
                \Log::info($data);
                \Log::info("=== Ruk jao ===");
                $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $data, $this->getDevEnvoirnment($receiver['user_id'], $receiver['envoirement'])) : false;
                $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $data) : false;
                return true;
            }
            return true;
        } catch (\Exception $ex) {
            $this->printException($ex, 'sendCommentPushNotification');
            return true;
        }
    }

    /**
     * send comment mention users notification
     * @param type $data
     * @param type $receivers
     * @param type $sender
     * @return boolean
     */
    public function sendMentionPushNotifications($data, $receivers, $sender = []) {
        try {
            if (!empty($data) && !empty($receivers) && !empty($sender)) {
                foreach ($receivers as $key => $receiver) {
                    $tokens = $this->prepareTokens([$receiver]);
                    $data = $this->prepareCommentData($data[0], $sender, $receiver);
                    $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $data, $this->getDevEnvoirnment($receiver['user_id'], $receiver['envoirement'])) : false;
                    $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $data) : false;
                }
                return true;
            }
            return true;
        } catch (\Exception $ex) {
            $this->printException($ex, 'sendMentionPushNotifications');
            return true;
        }
    }

    /**
     * send comment mention users notification
     * @param type $data
     * @param type $receivers
     * @param type $sender
     * @return boolean
     */
    public function sendTagUsersPushNotifications($data, $receivers, $sender = []) {
        try {
            if (!empty($data) && !empty($receivers) && !empty($sender)) {
                foreach ($receivers as $key => $receiver) {
                    $tokens = $this->prepareTokens([$receiver]);
                    $notiData = $this->prepareCommentData($data[0], $sender, $receiver, true);
                    $iosResponse = !empty($tokens['ios']) ? $this->sendIOSNotification($tokens, $notiData, $this->getDevEnvoirnment($receiver['user_id'], $receiver['envoirement'])) : false;
                    $androidResponse = !empty($tokens['android']) ? $this->sendAndroidNotification($tokens, $notiData) : false;
                }
                return true;
            }
            return true;
        } catch (\Exception $ex) {
            $this->printException($ex, 'sendTagUsersPushNotifications');
            return true;
        }
    }

    public function prepareCommentData($data, $sender, $receiver = [], $is_tag = false) {
        $response = [];
        if (!empty($data)) {
            $response['id'] = !empty($data['object_id']) ? (int) $data['object_id'] : (isset($data['id']) ? (int) $data['id'] : (int) $data['relation_id']);
            $response['badge'] = $this->getBadgeCount($receiver['user_id']);
            $response['type'] = $this->prepareNotificationType($data['domain']);
            $response['message'] = $this->prepareNotificationMessage($data['domain'], $sender);
            if (!$is_tag)
                $response['comment_text'] = $data['comment_text'];
            $response['post_id'] = !empty($data['relation_id']) ? $data['relation_id'] : "";
            $response['thumbnail'] = $this->preparePostMediathumb($data);
            $response['status'] = $data['status'];
            $response['sent_at'] = $this->parseCarbon($data['created_at']);
            $response['sender'] = $this->prepareUser($sender);
            $response['is_mute'] = false;
        }
        return $response;
    }

    /**
     * prepare push notification data array
     * @param type $data
     * @param type $sender
     * @return type
     */
    public function preparePushNotificationData($data, $sender, $receiver = []) {
        $response = [];
        if (!empty($data)) {
            $response['id'] = !empty($data['object_id']) ? (int) $data['object_id'] : $sender['uid'];
            $response['badge'] = $this->getBadgeCount($receiver['id']);
            $response['type'] = $this->prepareNotificationType($data['domain']);
            $response['message'] = $this->prepareNotificationMessage($data['domain'], $sender);
            if (!empty($data['relation_id'])) {
                $response['post_id'] = $data['relation_id'];
//                $response['thumbnail'] = $this->preparePostMediathumb($data);
                $response['thumbnail'] = !empty($data['thumb']) ? $data['thumb'] : "";
            }
            $response['status'] = $data['status'];
            $response['sent_at'] = $this->parseCarbon($data['created_at']);
            $response['sender'] = $this->prepareUser($sender);
            $response['is_mute'] = false;
        }
        return $response;
    }

    /**
     * get media URL
     * @param type $data
     * @param type $type
     * @return string
     */
    private function preparePostMediathumb($data, $type = 'thumb') {
        return !empty($data['thumb']) ? $data['thumb'] : "";
        $resp = "";
        $urlType = ($type == 'thumb') ? config('image.post_thumb1_url') : config('image.post_img_medium_url');
        switch ($data['media_type']) {
            case "P":
                $resp = config('image.s3_url') . $urlType . $data['media'];
                break;
            case "V":
                $resp = config('image.s3_url') . config('image.post_video_thumbnail_url') . str_replace(".mp4", ".jpg", $data['media']);
                break;
            default:
                "";
        }
        return $resp;
    }

    /**
     * prepare notification type
     * @param type $status
     * @return string
     */
    private function prepareNotificationType($status) {
        $type = "";
        switch ($status) {
            case "L":
                $type = 'like';
                break;
            case "F":
                $type = 'follow';
                break;
            case "R":
                $type = 'friend_request';
                break;
            case "AR":
                $type = 'accept_request';
                break;
            case "C":
                $type = 'comment';
                break;
            case "T":
                $type = 'taguser';
                break;
            case "M":
                $type = 'mentionuser';
                break;
            case "P":
                $type = 'newpost';
                break;
            default:
                break;
        }
        return $type;
    }

    /**
     * prepare push notification message
     * @param type $status
     * @param type $sender
     * @return string
     */
    private function prepareNotificationMessage($status, $sender) {
        $message = "";
        switch ($status) {
            case "L":
                $message = $sender['username'] . ' like your post';
                break;
            case "F":
                $message = $sender['username'] . ' started following you';
                break;
            case "R":
                $message = $sender['username'] . ' sent you a friend request';
                break;
            case "AR":
                $message = $sender['username'] . ' accepted your friend request';
                break;
            case "C":
                $message = $sender['username'] . ' commented on your post';
                break;
            case "T":
                $message = $sender['username'] . ' tagged you in a post';
                break;
            case "M":
                $message = $sender['username'] . ' mentioned you in a comment';
                break;
            default:
                $message = $sender['username'] . ' posted new post';
                break;
        }
        return $message;
    }

    /**
     * prepare user device tpkens (ios+android)
     * @param type $tokens
     * @return type
     */
    public function prepareTokens($tokens) {
        $response = ["ios" => [], 'android' => []];
        foreach ($tokens as $key => $token) {
            if ($token['device_type'] == 'android') {
                $response['android'][] = $token['device_token'];
            } else {
                $response['ios'][] = $token['device_token'];
            }
        }
        return $response;
    }

    /**
     * prepare push notification user array
     * @param type $user
     * @return type
     */
    public function prepareUser($user = []) {
        $response = [];
        if (!empty($user)) {
            $response['id'] = $user['uid'];
            $response['username'] = $user['username'];
            $response['picture'] = $user['thumb'];
        }
        return $response;
    }

    /**
     * Get user badge count
     * @param type $user_id
     * @return type
     */
    private function getBadgeCount($user_id) {
        $total = 0;
        if ($counts = FayvoActivity::getUserActivityCounts($user_id)) {
            foreach ($counts as $key => $value) {
                $total = $total + $value;
            }
        }
        return (int) $total;
    }

    public function setIosNotificationDataParameters($data) {
        return array(
            'aps' => array(
                'alert' => $data['message'],
                'badge' => (int) $data['badge'],
                'sound' => 'default',
                'content-available' => 1,
            ),
            'data' => $data,
        );
    }

    /**
     * send notification to IOS devices
     * @param type $tokens
     * @param string $data
     * @return boolean
     */
    public function sendIOSNotification($tokens, $data, $envoirement = 'production') {
        try {
            $payload = json_encode($this->setIosNotificationDataParameters($data));
            $deviceTokens = str_replace(array(' ', '<', '>'), '', $tokens['ios']);
            \Log::info("===== IOS Payload ===");
            \Log::info($payload);die;
            // FUNCTION NOTIFICATIONS        
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', config('push-notification.appNameIOS.certificate_' . $envoirement));
            stream_context_set_option($ctx, 'ssl', 'passphrase', 'push');
            //send notification 
            $fp = stream_socket_client(
                    config('push-notification.appNameIOS.ios_push_notification_' . $envoirement), $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx
            );
            $res = [];
            foreach ($deviceTokens as $deviceToken) {
                $apple_identifier = 'com.fayvoInternation.fayvo';
                $apple_expiry = time() + (20 * 24 * 60 * 60); // 20 days
                $msg = pack('C', 1) . pack('N', $apple_identifier) . pack('N', $apple_expiry) . pack('n', 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack('n', strlen($payload)) . $payload;
                $result = fwrite($fp, $msg, strlen($msg));
//                $this->checkAppleErrorResponse($fp);
                $res = json_encode($result);
            }
            fclose($fp);
            \Log::info("=== Notification sent ====");
            return true;
        } catch (\Exception $ex) {
            $this->printException($ex, 'sendIOSNotification');
            return true;
        }
    }

    public function checkAppleErrorResponse($fp) {

        //byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID). Should return nothing if OK.
        $apple_error_response = fread($fp, 6);
        //NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script and wait forever when there is no response to be sent.

        if ($apple_error_response) {
            \Log::info("==== Apple Error Found ===");
            //unpack the error response (first byte 'command" should always be 8)
            $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);

            if ($error_response['status_code'] == '0') {
                $error_response['status_code'] = '0-No errors encountered';
            } else if ($error_response['status_code'] == '1') {
                $error_response['status_code'] = '1-Processing error';
            } else if ($error_response['status_code'] == '2') {
                $error_response['status_code'] = '2-Missing device token';
            } else if ($error_response['status_code'] == '3') {
                $error_response['status_code'] = '3-Missing topic';
            } else if ($error_response['status_code'] == '4') {
                $error_response['status_code'] = '4-Missing payload';
            } else if ($error_response['status_code'] == '5') {
                $error_response['status_code'] = '5-Invalid token size';
            } else if ($error_response['status_code'] == '6') {
                $error_response['status_code'] = '6-Invalid topic size';
            } else if ($error_response['status_code'] == '7') {
                $error_response['status_code'] = '7-Invalid payload size';
            } else if ($error_response['status_code'] == '8') {
                $error_response['status_code'] = '8-Invalid token';
            } else if ($error_response['status_code'] == '255') {
                $error_response['status_code'] = '255-None (unknown)';
            } else {
                $error_response['status_code'] = $error_response['status_code'] . '-Not listed';
            }
            \Log::info($error_response);
//            echo '<br><b>+ + + + + + ERROR</b> Response Command:<b>' . $error_response['command'] . '</b>&nbsp;&nbsp;&nbsp;Identifier:<b>' . $error_response['identifier'] . '</b>&nbsp;&nbsp;&nbsp;Status:<b>' . $error_response['status_code'] . '</b><br>';
//            echo 'Identifier is the rowID (index) in the database that caused the problem, and Apple will disconnect you from server. To continue sending Push Notifications, just start at the next rowID after this Identifier.<br>';

            return true;
        } else {
            \Log::info("==== No Apple Error Found ===");
        }
        return false;
    }

    /**
     * send notifications to android devices
     * @param type $tokens
     * @param type $data
     * @return boolean
     */
    public function sendAndroidNotification($tokens, $data) {
        try {
            $fields = array
                (
                'registration_ids' => str_replace(array(' ', '<', '>'), '', $tokens['android']),
//                'notification' => [
//                    "title" => $data['message'], "body" => ""
//                ],
                'data' => $data
            );
            \Log::info("=== Android Server key for push notifications ===");
            \Log::info(config('push-notification.appNameAndroid.apiKey'));
            \Log::info("=== Android Notification Data ====");
            \Log::info($fields);
            $headers = array(
                'Authorization: key=' . config('push-notification.appNameAndroid.apiKey'),
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            \Log::info("== Notification sent ==");
            return true;
        } catch (\Exception $ex) {
            $this->printException($ex, 'sendAndroidNotification');
            return true;
        }
    }

}
