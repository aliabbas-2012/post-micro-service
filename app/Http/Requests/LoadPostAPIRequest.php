<?php

namespace App\Http\Requests;

class LoadPostAPIRequest extends BaseRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        \Log::info("---> Post Post Detail Inputs Received <---");
        \Log::info(print_r($this->inputs, true));
        $rules = [
            "type" => "required|in:A,P,PD"//PD product
        ];
        if (isset($this->inputs['type']) && $this->inputs['type'] == "A") {
            $api_rules = [
                "api.source_type" => "required",
                "api.source_id" => "required",
                "api.item_type_number" => "required|integer|in:1,2,3,4,5,6,8,10,9",
            ];
            $rules = array_merge($rules, $api_rules);
        }
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            "type.required" => "Information missing",
            "type.in" => "Provided information is invalid",
            "api.source_type.required" => "Source information missing",
            "api.source_id.required" => "Source information missing",
            "api.item_type_number.required" => "Source information missing",
            "api.item_type_number.integer" => "Invalid source information missing",
            "api.item_type_number.in" => "Invalid source information missing",
        ];
    }

}
