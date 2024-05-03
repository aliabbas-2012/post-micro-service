<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class FriendPostRequest extends BaseRequest {

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
            "limit" => "required|integer|min:1",
            "offset" => "required|integer|min:0",
            "less_than" => "required|integer|min:0",
            "greater_than" => "required|integer|min:0",
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'offset.required' => 'Param information missing',
            'offset.integer' => 'Invalid param type received',
            'offset.min' => 'Invalid param value received',
            'limit.required' => 'Param information missing',
            'limit.integer' => 'Invalid param type received',
            'limit.min' => 'Invalid param value received',
            'less_than.required' => 'Param information missing',
            'less_than.integer' => 'Invalid param type received',
            'less_than.min' => 'Invalid param value received',
            'greater_than.required' => 'Param information missing',
            'greater_than.integer' => 'Invalid param type received',
            'greater_than.min' => 'Invalid param value received',
        ];
    }

}
