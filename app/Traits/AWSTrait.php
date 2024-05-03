<?php

/**
 * Description of CommonTrait
 *
 * @author farazirfan
 */

namespace App\Traits;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use AWS;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use App\Traits\CommonTrait;

trait AWSTrait {

    // use \App\Http\Controllers\ResponseTrait;
    // public $aws;
    public $cognito;

    public function aws() {
        $aws = new \Aws\Sdk(config('cognito'));
        return $aws;
    }

    public function buildDynamoDBClient() {
        $aws = $this->aws();
        $dynamoDb = $aws->createDynamoDb();
        return $dynamoDb;
    }

    public function getMarshaler() {
        return new Marshaler();
    }

    /**
     * Log exception into dynamo db
     * @param type $exception
     * @param type $method
     * @param type $envoirnment
     * @return boolean
     */
    public function logExceptionDynamo($exception, $method = null, $envoirnment = "staging", $data = []) {
        try {
            $log["id"] = time() . uniqid();
            $log["created_at"] = date("Y-m-d h:i:s");
            $log["envoirnment"] = $envoirnment;

            $log["file"] = $exception->getFile();
            $log["line"] = $exception->getLine();
            $log["message"] = $exception->getMessage();

            $log["method"] = $method;
            $log["user_id"] = isset($data["user_id"]) ? $data["user_id"] : 0;
            $log["data"] = json_encode($data);
            \Log::info("=== exception log data ===");
            \Log::info($log);
            $item = $this->getMarshaler()->marshalJson(json_encode($log));
            $params = [
                'TableName' => 'fayvo_logs',
                'Item' => $item
            ];
            $result = $this->buildDynamoDBClient()->putItem($params);
            return $result;
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . "=>" . $ex->getFile() . "(" . $ex->getLine() . ")";
            \Log::info("=== AWS Trait Log Exceptio Dynamo ===");
            \Log::info($message);
            return false;
        }
    }
    /**
     * This is temporary method will be removed after testing
     * @param type $method
     * @param type $envoirnment
     * @param type $data
     * @return type
     */
    public function logTempExceptionDynamo($method = null, $envoirnment = "staging", $data = []) {
        try {
            $log["id"] = time() . uniqid();
            $log["created_at"] = date("Y-m-d h:i:s");
            $log["envoirnment"] = $envoirnment;


            $log["method"] = $method;
            $log["user_id"] = isset($data["user_id"]) ? $data["user_id"] : 0;
            $log["data"] = json_encode($data);
           
            $item = $this->getMarshaler()->marshalJson(json_encode($log));
            $params = [
                'TableName' => 'fayvo_logs',
                'Item' => $item
            ];
            $result = $this->buildDynamoDBClient()->putItem($params);
            return $result;
        } catch (\Exception $ex) {
            
        }
    }
    
    /**
     * Create Sqs Client
     * @return type
     */
    public function buildSqsDBClient() {
        $aws = $this->aws();
        $client = $aws->createSqs();
        return $client;
    }

}
