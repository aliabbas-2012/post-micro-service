<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class PostCommentRequest extends BaseRequest {

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
        \Log::info("-- post comment inputs ---");
        \Log::info(print_r($this->inputs, true));
        if (isset($this->inputs['comment_id'])) {
            return [
                'comment_id' => 'required|integer|min:1'
            ];
        } else if (isset($this->inputs['last_id'])) {
            return [
                'last_id' => 'required|integer|min:1',
                'post_id' => 'required|integer|min:1'
            ];
        } else {
            return [
                'post_id' => 'required',
                'comment' => 'required',
            ];
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'comment_id.required' => $this->errors['comment_id_required'],
            'last_id.required' => $this->errors['last_id_required'],
            'post_id.required' => $this->errors['post_id_required'],
            'comment.required' => $this->errors['comment_required'],
        ];
    }

}
