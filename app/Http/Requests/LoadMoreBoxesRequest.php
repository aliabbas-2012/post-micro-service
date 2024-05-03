<?php

namespace App\Http\Requests;

class LoadMoreBoxesRequest extends BaseRequest {

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
                'offset' => 'required|integer|min:0'
            ];
        }
        return [
            'offset' => 'required|integer|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            "profile_id.required" => $this->errors['profile_id_required'],
            "offset.required" => $this->errors['offset_required'],
            "offset.integer" => $this->errors['offset_integer'],
            "offset.min" => $this->errors['offset_min'],
        ];
    }

}
