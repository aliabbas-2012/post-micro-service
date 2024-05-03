<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Traits;

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use GuzzleHttp\Client;

trait SqsTrait {

    /**
     * Trigger ML End-Poing for Interest saving via {SQS}
     * @param type $user
     * @param type $post
     * @param type $modal
     * @param type $is_like
     */
    public function triggerMlSqs($arr, $model, $isDel = false) {
        try {
            $payload = $this->prepareMlHistoryInterestArr($arr['user'], $arr['post'], $model, $isDel);
            $body = $this->prepareSqsBody($payload, $this->getMlRoute($payload['action']), 60);
            $es_body = $this->prepareSqsBody($this->prepareEsModel($model, $arr['post'], $isDel), 'sync/action/es/model', 0);
            \Log::info(print_r($body, true));
//            return $this->testingRoute($this->prepareEsModel($model, $isDel)); // For local development
            $client = $this->buildSqsDBClient();
            $response = $client->sendMessage($body);
            $result = $client->sendMessage($es_body);
            \Log::info("-- SQS send message response ---");
            \Log::info(print_r($response, true));
            \Log::info(print_r($result, true));
            return $response;
        } catch (AwsException $ex) {
            $message = $ex->getAwsErrorCode() . '=>' . $ex->getAwsErrorMessage();
            \Log::info($message);
            // output error message if fails
            return false;
        } catch (\Exception $ex) {
            $log = [];
            $log["message"] = $ex->getMessage();
            $log["file"] = $ex->getFile();
            $log["line"] = $ex->getLine();
            \Log::info(print_r($log, true));
            return false;
        }
    }

    /**
     * Prepare ML action interest array
     * @param type $user
     * @param type $post
     * @param type $modal
     * @param type $isDel
     * @return type
     */
    public function prepareMlHistoryInterestArr($user, $post, $modal, $isDel = false) {
        $payload = [];
        $object = [
            "id" => $user->id,
            "object_type" => config("general.object_prefix.user"),
            "location" => ["ip" => $modal->client_ip_address, "lat" => $modal->lat, "lon" => $modal->lon]
        ];
        $payload["object"] = $object;
        $payload["action"] = $isDel ? "DC" : "C";
        $payload["action_id"] = $modal->id;
        $payload["action_date"] = date("Y-m-d", strtotime($modal->created_at));
        $payload["action_user"] = $modal->user_id;
        $payload["post"] = [
            "id" => $post->id,
            "user_id" => $post->user_id,
            "post_type_id" => $post->post_type_id,
            "item_type_number" => $post->item_type_number,
            "source_id" => $post->source_id,
            "source_type" => $post->source_type,
            "location" => [
                "ip" => $post->client_ip_address,
                "lat" => !empty($post->client_ip_latitude) ? $post->client_ip_latitude : null,
                "lon" => !empty($post->client_ip_longitude) ? $post->client_ip_longitude : null
            ]
        ];
        if ($post->post_type_id == 7) {
            if ($post->source_type != 'google') {
                $payload["post"]["api_attribute_id"] = $post->api_attribute_id;
            } else {
                $payload["post"]["location_id"] = $post->location_id;
            }
        }
        return $payload;
    }

    /**
     * Prepare Es Model
     * @param type $modal
     * @param type $isDel
     * @return type
     */
    public function prepareEsModel($modal, $post = [], $isDel = false) {
        $payload = [];
        $payload['action'] = $isDel ? 'DC' : 'C';
        $payload['model'] = $modal->toArray();
        $payload['model']['owner_id'] = !empty($post) ? $post->user_id : 0;
        return $payload;
    }

    /**
     * Prepare Sqs Body
     * @param type $payload
     * @return int
     */
    public function prepareSqsBody($payload = [], $route, $delay = 60) {
        $body = [
            'DelaySeconds' => (int) $delay,
            'MessageAttributes' => [
                "payload" => [
                    'DataType' => "String",
                    'StringValue' => json_encode($payload, true)
                ],
                'beanstalk.sqsd.taskname' => [
                    'DataType' => 'String',
                    'StringValue' => "task1"
                ],
                'beanstalk.sqsd.path' => [
                    'DataType' => 'String',
                    'StringValue' => "schedule/$route"
                ],
                'beanstalk.sqsd.scheduled_time' => [
                    'DataType' => 'String',
                    'StringValue' => '2020-06-06TT14:46:00'
                ]
            ],
            'MessageBody' => "elasticbeanstalk scheduled job",
            'QueueUrl' => config('general.aws_sqs.ml_interest_history_queue')
        ];
        return $body;
    }

    /**
     * Get Sqs path
     * @param type $action
     * @return string
     */
    private function getMlRoute($action) {
        $route = "sync/action/comment";
        if ($action == "DC") {
            $route = "sync/action/delete-comment";
        }
        return $route;
    }

    public function testingRoute($payload = []) {
        $route = "comment";
        if ($payload['action'] == "DC") {
            $route = "delete-comment";
        }
        $url = "http://127.0.0.1:8000/schedule/sync/action/es/model";
        $client = new Client();
        $headers = [
            "headers" => [
                "X-Aws-Sqsd-Attr-Payload" => json_encode($payload, true)
            ]
        ];
        $response = "";
        $result = $client->get($url, $headers);
        if ($result->getStatusCode() == 200) {
            return true;
        }
        return false;
    }

}
