<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Repositories;

use Event;
use App\Repositories\Contracts\PostRepository;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\PostTagUser;
use App\Helpers\PostRelationHelper;

/**
 * Description of EloquentPostComment
 *
 * @author rizwan
 */
class EloquentPost extends AbstractEloquentRepository implements PostRepository {

    use \App\Traits\CommonTrait;
    use \App\Traits\PostTrait;
    use \App\Traits\AWSTrait;

    public function getPostsByIds($post_ids, $user_id, $relaions = true) {

        $query = $this->model->select("id", "text_content", "created_at", "local_db_path", "user_id", "location_id")
                ->whereIn("id", $post_ids)
                ->with(PostRelationHelper::getRelations($user_id, $relaions));

        return $query->orderByRaw("FIELD(id , '" . implode("','", $post_ids) . "') ASC");
    }

    public function getSinglePost(array $data) {
        $query1 = ['user' => function ($query) {
                $query->select('id', 'uid', 'full_name', 'username', 'picture', 'bucket', 'archive', 'is_live');
            }];
        $query2 = ['place' => function ($query) {
                $query->select('id', 'latitude', 'longitude', 'location_name', 'fs_location_id', 'address');
            }];
        $relations = array_merge($query1, $query2, $this->getRelations($data['user_id'], false));
        $condition = !empty($data["local_db_path"]) ? ['local_db_path' => $data['local_db_path']] : ['id' => $data['post_id']];
        $post = $this->findOneWithRelations($condition, $relations);
        return $post;
    }

    /**
     * get posts by box
     * @param type $data
     * @return type
     */
    public function getBoxPosts($data, $permissions) {

        $query = $this->model->distinct("user_posts_id")->select("user_posts_id");
        if ($data['less_than'] > 0) {
            $query->where('user_posts_id', '<', $data['less_than']);
        }
        $posts = $this->getBoxPostQuery($query, $data, $permissions)
                ->orderBy('user_posts_id', 'DESC')->limit($data['limit'])
                ->get();
        return (!empty($posts)) ? $posts : [];
    }

    /**
     * 
     * @param type $query
     * @param type $data
     * @param type $permissions
     * @return type
     */
    public function getBoxPostsCount($data, $permissions) {

        $query = $this->model->where("user_posts_id", "<>", 0);
        $query = $this->getBoxPostQuery($query, $data, $permissions)->get();
        return $query->count();
    }

    private function getBoxPostQuery($query, $data, $permissions) {
        $user_id = $data['user_id'];
        $query->where('box_id', '=', $data['box_id'])->where("status", "=", "A")->where("archive", "=", false)
                ->with(["post" => function ($sql) use ($user_id) {
                        $sql->select("id", "user_id", "created_at", "post_type_id", \DB::raw("id as postable_id,postable_type"));
                        $sql->with(PostRelationHelper::getRelations($user_id, false));
                        $sql->with(['user' => function ($query) {
                                $query->select('id', "uid", 'username', 'picture', 'bucket');
                            }]);
                    }]);
        return $query->whereHas('box', function ($sql) use ($permissions) {
                    $sql->whereIn('status', $permissions);
                });
    }

    /**
     * 
     * @param type $user_id
     * @return type
     */
    public function getPostCount($user_id) {
        return $this->model->where("user_id", "=", $user_id)->count();
    }

    /**
     * get post tag users
     * @param type $data
     * @return type
     */
    public function getPostTagUsers($loging_user, $data) {
        $data['search_key'] = $this->trim_string($data['search_key']);
        $users = \App\Models\PostTagUser::getPostTagUsers($loging_user, $data['post_id'], $data, $data['search_key']);
        return ($users->isEmpty()) ? [] : $users->toArray();
    }

    /**
     * Get post by id
     * @param type $ids
     * @param type $user_id
     * @return boolean
     */
    public function getPostsById($ids, $user_id) {
        if ($posts = $this->model
                        ->with(PostRelationHelper::getRelations($user_id, false))->whereIn("id", $ids)->get()) {
            return $posts;
        }
        return false;
    }

    public function getSharedPost($id) {
        if ($post = $this->model->where('id', '=', $id)->with('latestPostMedia')->first()) {
            return $post->toArray();
        }
        return [];
    }

    /**
     * Track UnAuthorized Views will help us which user is viewing the un authorized posts
     * @param type $id
     * @param type $user_id
     */
    public function trackUnAuthorizedViews($id, $user_id) {
        $data = ["id" => $id, "user_id" => $user_id, 'environment' => env("APP_ENV", "staging"), "action_date" => Carbon::now()->format("Y-m-d\TH:i:s\Z"), "timestamp" => Carbon::now()->timestamp];

        $marshaler = $this->getMarshaler();
        $params = [
            'TableName' => config("cognito.dynamo_db_un_authorized_views"),
            'Item' => $marshaler->marshalJson(json_encode($data))
        ];
        $response = $this->buildDynamoDBClient()->putItem($params);
    }

    /**
     * get post/box id against short_code
     * @param type $short_code
     * @return type
     */
    public function getDynamoDbObject($short_code) {
        $req = [
            "id" => $short_code
        ];
        $key = $this->getMarshaler()->marshalJson(json_encode($req));
        $params = [
            'TableName' => config("cognito.short_code_dynamodb_table"),
            'Key' => $key
        ];
        $res = $this->buildDynamoDBClient()->getItem($params);

        if (!empty($res["Item"])) {
            $data = $this->getMarshaler()->unmarshalItem($res["Item"]);
            if (in_array($data["param_type"], ["box_id", "post_id", "post"])) {
                return $data["param_id"];
            }
        }

        return config("general.no_content_int");
    }
    /**
     * This is temporary method will be removed after testing
     * @param type $inputs
     * @return type
     */
    public function storeTemporaryLogsInDynamoDB($inputs) {

        if ($inputs["less_than"] == 0 && $inputs["greater_than"] == 0) {
            $inputs = array_merge($inputs, \DB::getQueryLog());
            return $this->logTempExceptionDynamo('home', env("APP_ENV", "local"), $inputs);
        }
    }

}
