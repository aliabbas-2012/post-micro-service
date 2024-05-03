<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class HomeRequest extends BaseRequest {

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
            'offset' => 'required',
            'local_path' => 'array',
        ];
    }
    
    public function validationData() {
        parent::validationData();

        $this->inputs['greater_than'] = !empty($this->inputs['greater_than']) ? $this->inputs['greater_than'] : 0;
        $this->inputs['less_than'] = !empty($this->inputs['less_than']) ? $this->inputs['less_than'] : 0;
        $this->inputs['limit'] = !empty($this->inputs['limit'] && $this->inputs["limit"] > 0) ? $this->inputs['limit'] : 40;
        return $this->inputs;
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'limit.required' => $this->errors['limit_required'],
            'offset.required' => $this->errors['offset_required'],
        ];
    }

}
