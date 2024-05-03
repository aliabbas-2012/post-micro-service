<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Rules\CheckBoxes;
use App\Models\Box;

class PostCreateRequest extends BaseRequest {

    use \App\Traits\CommonTrait;

    public $user, $boxes;

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
    public function rules(Request $request) {

        $rules = [

            'media' => ['required', 'array'],
            'box_ids' => ['required', 'array'],
            'media' => 'required|array',
            "local_db_path" => "required",
            'web_url' => 'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/'
        ];
        $user = $this->getCurrentUserForRequest($request);

        if (!empty($user) && $user instanceof User) {
            $this->user = $user;
            $boxes = $this->getBoxes($request->get("data"));
            $rules["box_ids"][] = new CheckBoxes(count($boxes));
            $this->boxes = $boxes;
        } else {
            $rules["user_id"] = "required";
        }
        return $rules;
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages() {
        return [
            'user_id.required' => 'Sorry! your session has expired',
            'box_ids.required' => 'Boxes information is missing',
            'box_ids.array' => 'Boxes information is missing',
            'local_db_path.required' => 'Post Key is missing',
        ];
    }

    private function getBoxes($input) {
        if (isset($input["box_ids"])) {
            return Box::select("id", "name", "status")
                            ->where("user_id", "=", $this->user->id)
                            ->whereIn("id", $input["box_ids"])->where("archive", "=", false)->get();
        }
        return [];
    }

}
