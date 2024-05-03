<?php

namespace App\Http\Requests;

class PostProfileIdValidation extends BaseRequest {

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
        if (isset($this->inputs['profile_id'])) {
            return [
                'profile_id' => "required",
                'less_than' => 'required|integer|min:1'
            ];
        }
        return [
            'less_than' => 'required|integer|min:1'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            "profile_id.required" => 'profile_id_required',
            "less_than.required" => 'less_than_required',
            "less_than.integer" => 'less_than_integer',
            "less_than.min" => 'less_than_min',
            "is_translate" => true
        ];
    }

}
