<?php

namespace App\Helpers;

/**
 * Description of Lambda
 *
 * @author ali
 */
use Aws\Lambda\LambdaClient;

class LambdaCaller {

    private $client;

    public function __construct() {
        
    }

    public function setClient() {
        $this->client = LambdaClient::factory([
                    'version' => 'latest',
                    'region' => 'us-west-2',
        ]);
    }

    /**
     * 
     * @param type $box
     * @return string
     */
    public function updateShortCode($box) {
        $this->setClient();
        $output = false;
        if (empty($box["short_code"])) {
            try {
               
                $url = config('image.fayvo-sample-cdn-url') . config("image.empty_new_box_url");
                $id = $box["id"];
                $data = [
                    "path" => "/create",
                    "body" => json_encode([
                        "data" => "box_id=$id&url=$url"
                    ])
                ];
                $result = $this->client->invoke([
                    // The name your created Lamda function
                    'InvocationType' => 'RequestResponse',
                    'FunctionName' => config("cognito.short_code_lambda_function"),
                    'Payload' => json_encode($data)
                ]);

                $response = json_decode($result->get('Payload')->getContents(), true);
                if ($response["statusCode"] == 200) {
                    return $shortCode = json_decode($response["body"], true)["shortKey"];
                }
            } catch (\Exception $ex) {
                
            }
        }
        return $output;
    }

    /**
     * 
     * @param type $local_db_path
     * @return string
     */
    public function getMediaCollage($local_db_path) {
        $this->setClient();
        try {

            $data = [
                "local_db_path" => $local_db_path,
                "environment" => env("APP_ENV") == "local" || env("APP_ENV") == "staging" ? "staging" : "production"
            ];
            $result = $this->client->invoke([
                // The name your created Lamda function
                'InvocationType' => 'RequestResponse',
                'FunctionName' => "MakeCollage",
                'Payload' => json_encode($data)
            ]);

            $response = json_decode($result->get('Payload')->getContents(), true);


            if (isset($response["statusCode"]) && $response["statusCode"] == 200) {
                $response = json_decode($response["body"], true);
                $response["is_primary"] = true;
                return $response;
            }
            return [];
        } catch (\Exception $ex) {
            $exception["message"] = $ex->getMessage();
            $exception["file"] = $ex->getFile();
            $exception["line"] = $ex->getLine();
            \Log::error("=== LambdaCallerException ===");
            \Log::error($exception);


            return [];
        }
    }

}
