<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use App\Models\PostComment;
use App\Models\UserPost;

/**
 * Description of TestController
 *
 * @author ali
 */
class TestController extends Controller {

    use \App\Traits\CacheTrait;

    public function getComment(Request $request) {
        try {
            $key = "uid";
            $value = "02bbe40f-8a58-11e8-bc0f-3417eb717aa1";

            $this->getCurrentUserCache($key, $value);
//            return $this->respondWithArray(PostComment::find($id)->toArray());
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

    public function getPost(Request $request) {
        try {
            $ip = $request->getClientIp();
            $inputs = $request->all();
            if (!isset($inputs['key']) || $inputs['key'] != 'fayv@5d1df835ed283') {
                echo "<center><p style='color:red;font-size:40px;margin-top:20%;'>Sorry! you are not allowed to view this page.</p></center>";
                die;
            }
            $post = [];
            $userUrl = "http://localhost/fayvo/user/dev-test/view-profile?key=fayv@5d1df835ed283&search=";
            $boxUrl = "http://localhost/fayvo/user/dev-test/view-box?key=fayv@5d1df835ed283&type=box&search=";
            if (isset($inputs['id']) && $inputs['id'] > 0) {
                $columns = [\DB::raw("*,id as postable_id")];
                $post = UserPost::select($columns)
                                ->where("id", "=", $inputs['id'])
                                ->with(['user', 'postTags.user', 'postBoxesPivot', 'postable'])->first();
                if (env("APP_ENV") == "production") {
                    $userUrl = "http://fayvo-user-production.us-west-2.elasticbeanstalk.com/dev-test/view-profile?key=fayv@5d1df835ed283&search=";
                    $boxUrl = "http://fayvo-user-production.us-west-2.elasticbeanstalk.com/dev-test/view-box?key=fayv@5d1df835ed283&type=box&search=";
                } else if (env("APP_ENV") == "staging") {
                    $userUrl = "http://user.us-west-2.elasticbeanstalk.com/dev-test/view-profile?key=fayv@5d1df835ed283&search=";
                    $boxUrl = "http://user.us-west-2.elasticbeanstalk.com/dev-test/view-box?key=fayv@5d1df835ed283&type=box&search=";
                }
            }
            return view("view-post", compact("ip", "post", "userUrl", "boxUrl"));
        } catch (\Exception $ex) {
            return $this->sendExceptionError($ex);
        }
    }

}
