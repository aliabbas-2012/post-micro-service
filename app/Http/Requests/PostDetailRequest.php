<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class PostDetailRequest extends BaseRequest {

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
        if (isset($this->inputs["post_id"])) {
            return ['post_id' => 'required|integer'];
        } else {
            return [];
        }
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'post_id.required' => 'post_id_required',
            'is_translate' => true
        ];
    }

}
