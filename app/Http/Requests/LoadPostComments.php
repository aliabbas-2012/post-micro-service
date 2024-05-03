<?php

namespace App\Http\Requests;

class LoadPostComments extends BaseRequest {

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
            'type' => "required|in:up,down",
            'post_id' => "required",
            'last_id' => "required|integer|min:0"
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'post_id.required' => $this->errors['post_id_required'],
            'type.required' => $this->errors['type_required'],
            'type.in' => $this->errors['type_in'],
            'last_id.required' => $this->errors['last_id_required'],
            'last_id.integer' => $this->errors['last_id_integer'],
            'last_id.min' => $this->errors['last_id_min'],
        ];
    }

}
