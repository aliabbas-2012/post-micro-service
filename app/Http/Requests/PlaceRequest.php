<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class PlaceRequest extends BaseRequest {

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
        return [
            'limit' => 'required',
//            "location" => "array"
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'limit.required' => 'Limit information is missing',
        ];
    }

}
