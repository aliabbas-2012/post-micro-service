<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SharePostController {

    private $post;

    use \App\Traits\MediaBucketPathTrait;

    public function __construct() {
        $this->post = app()->make(\App\Repositories\Contracts\PostRepository::class);
    }

    /**
     * Process shared post
     * @param Request $request
     * @return type
     */
    public function sharedPost(Request $request) {
        try {
            $inputs = $request->all();
            $validate = Validator::make($inputs, ['auth' => 'required', 'id' => 'required']);
            if ($validate->fails()) {
                return response()->json(['message' => "URL has been expired or invalid"]);
            }
            $post_id = $inputs['id'];
            $auth = $inputs['auth'];
            $url = $this->getPostThumbUrl($post_id);
            return view('deeplink', compact("post_id", "auth", "url"));
//            return $this->redirectRequest($this->isSmartDeviceRequested($request), $inputs);
        } catch (\Exception $ex) {
            $message = $ex->getMessage() . '=>' . $ex->getFile() . '(' . $ex->getLine() . ')';
            $this->redirectRequest([], []);
        }
    }

    /**
     * Check is mobile device request
     * @param type $request
     * @param type $inputs
     * @return boolean
     */
    public function isSmartDeviceRequested($request) {
        return ['device' => true, 'type' => 'ios', 'url' => config('general.itune_store')];
        $user_agent = $request->header('user-agent');
        $iPod = stripos($user_agent, "iPod");
        $iPhone = stripos($user_agent, "iPhone");
        $iPad = stripos($user_agent, "iPad");
        $Android = stripos($user_agent, "Android");
        if ($iPod || $iPhone || $iPad) {
            return ['device' => true, 'type' => 'ios', 'url' => config('general.itune_store')];
        } else if ($Android) {
            return ['device' => true, 'type' => 'android', 'url' => config('general.google_store')];
        }
        return [];
    }

    /**
     * Redirect deep link URL
     * @param type $is_device
     * @param type $inputs
     * @return type
     */
    public function redirectRequest($device = [], $inputs) {
        if (!empty($device)) {
            $this->trackSharedPost($inputs);
            $env = env('APP_ENV', 'staging');
            $link = config('general.shared_post_deeplink_' . $env) . "?id=" . $inputs['id'] . '&url=' . $this->getPostThumbUrl($inputs['id']);
            return redirect()->to($link);
        }
        return redirect()->to(config('general.shared_post_deeplink_browser'));
    }

    public function redirectToStore(Request $request) {
        if ($response = $this->isSmartDeviceRequested($request)) {
            $response['url'] = ($response['type'] == 'ios') ? config('general.itune_store') : config('general.google_store');
            return redirect()->to($response['url']);
        }
        return redirect()->to(config('general.shared_post_deeplink_browser'));
    }

    /**
     * Track shared post
     * @param type $inputs
     * @return type
     */
    public function trackSharedPost($inputs) {
        return $inputs;
    }

    /**
     * Get post thumnbanil URL
     * @param type $post_id
     * @return type
     */
    public function getPostThumbUrl($post_id) {
        $url = "";
        if ($post = $this->post->getSharedPost($post_id)) {
            $url = $post['latest_post_media']['thumb'];
            if ($post['post_type_id'] == 5) {
                $url = $this->getCollageUrl($post['local_db_path'] . '.jpg');
            }
        }
        return $url;
    }

}
