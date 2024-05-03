<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Requests;

use Pearl\RequestValidate\RequestAbstract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @author rizwan
 */
class BaseRequest extends RequestAbstract {

    use \App\Traits\MessageTrait;

    public $inputs;

    public function validationData() {
        if ($this->isJson()) {
            $this->inputs = $this->isJsonRequest();
        } else {
            $this->inputs = $this->isSimpleRequest();
        }
        return $this->inputs;
    }

    public function isJsonRequest() {
        $inputs = [];
        $bodyJson = $this->json()->get('body-json');
        if (isset($bodyJson['data'])) {
            $inputs = $bodyJson['data'];
        } else {
            $inputs = json_decode($this->getContent(), true);
            $inputs = isset($inputs['data']) ? $inputs['data'] : $inputs;
        }
        /**
         * Setting ip of every request in payload
         */
        $inputs["ip"] = $this->header("remote-ip");
        return $inputs;
    }

    public function isSimpleRequest() {
        $inputs = [];
        if ($this->get('data') != null) {
            $inputs = $this->get('data');
        } else {
            $inputs = $this->all();
        }
        return $inputs;
    }

    public function failedValidation(Validator $validator) {
        throw new ValidationException($validator, response()->json(['message' => $this->formatErrors($validator), 'fv_server' => config("general.show_errors_on_app")], 400));
    }

    public function formatErrors(Validator $validator) {
        return $this->translateMsg($validator);
    }

    private function translateMsg($validator) {
        $response = $validator->errors()->first();
        $errorMessages = $this->messages();
        if (!empty($errorMessages) && isset($errorMessages['is_translate'])) {
            $failedRules = $validator->failed();
            $baseKey = array_keys($failedRules)[0];
            $baseFailedRule = strtolower(array_keys($failedRules[$baseKey])[0]);
            if (isset($errorMessages["$baseKey.$baseFailedRule"]))
                $response = trans("messages." . $errorMessages["$baseKey.$baseFailedRule"]);
        }
        return $response;
    }

}
