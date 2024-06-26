<?php

//app/Http/Controllers/ResponseTrait.php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

trait ResponseTrait {

    /**
     * Status code of response
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Fractal manager instance
     *
     * @var Manager
     */
    protected $fractal;

    /**
     * Set fractal Manager instance
     *
     * @param Manager $fractal
     * @return void
     */
    public function setFractal(Manager $fractal) {
        $this->fractal = $fractal;
    }

    /**
     * Getter for statusCode
     *
     * @return mixed
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * Setter for statusCode
     *
     * @param int $statusCode Value to set
     *
     * @return self
     */
    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Send custom data response
     *
     * @param $status
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCustomResponse($message, $status = 400) {
        $payload = ['message' => $message];
        if (!in_array($status, [200, 201])) {
            $payload["fv_server"] = config("general.show_errors_on_app");
        }
        if (!empty($status) && in_array($status, [400, 401])) {
            $payload['error_type'] = "$status";
        }
        return response()->json($payload, $status);
    }

    /**
     * Send this response when api user provide fields that doesn't exist in our application
     *
     * @param $errors
     * @return mixed
     */
    public function sendUnknownFieldResponse($errors) {
        return response()->json((['status' => 400, 'unknown_fields' => $errors]), 400);
    }

    /**
     * Send this response when api user provide filter that doesn't exist in our application
     *
     * @param $errors
     * @return mixed
     */
    public function sendInvalidFilterResponse($errors) {
        return response()->json((['status' => 400, 'invalid_filters' => $errors]), 400);
    }

    /**
     * Send this response when api user provide incorrect data type for the field
     *
     * @param $errors
     * @return mixed
     */
    public function sendInvalidFieldResponse($errors) {
        return response()->json((['status' => 400, 'invalid_fields' => $errors]), 400);
    }

    /**
     * Send this response when a api user try access a resource that they don't belong
     *
     * @return string
     */
    public function sendForbiddenResponse() {
        return response()->json(['status' => 403, 'message' => 'Forbidden'], 403);
    }

    /**
     * Send 404 not found response
     *
     * @param string $message
     * @return string
     */
    public function sendNotFoundResponse($message = '') {
        if ($message === '') {
            $message = 'The requested resource was not found';
        }

        return response()->json(['status' => 404, 'message' => $message, 'fv_server' => config("general.show_errors_on_app")], 404);
    }

    /**
     * Send empty data response
     *
     * @return string
     */
    public function sendEmptyDataResponse() {
        return response()->json(['data' => new \StdClass()]);
    }

    /**
     * Return collection response from the application
     *
     * @param array|LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection $collection
     * @param \Closure|TransformerAbstract $callback
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithCollection($collection, $callback, $columns = []) {
        $resource = new Collection($collection, $callback);
        //set empty data pagination
        if (empty($collection)) {
            $collection = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $resource = new Collection($collection, $callback);
        }
//        $resource->setPaginator(new IlluminatePaginatorAdapter($collection));
        $rootScope = $this->fractal->createData($resource);
        return $this->respondWithArray($rootScope->toArray(), [], $columns);
    }

    /**
     * Return single item response from the application
     *
     * @param Model $item
     * @param \Closure|TransformerAbstract $callback
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithItem($item, $callback, $column = []) {
        $resource = new Item($item, $callback);
        $rootScope = $this->fractal->createData($resource);
        return $this->respondWithArray($rootScope->toArray(), [], $column);
    }

    /**
     * Return a json response from the application
     *
     * @param array $array
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithArray(array $array, array $headers = [], $columns = []) {
        $array = !empty($columns) ? array_merge($array, $columns) : $array;
        return response()->json($array, $this->statusCode, $headers);
    }

    /**
     * send custom success response without data
     * @param type $message
     * @return type
     */
    public function sendCustomSuccessResponse($message) {
        return response()->json(['success' => true, 'message' => $message], 200);
    }

    /**
     * send custom error response without data
     * @param type $message
     * @return type
     */
    public function sendCustomErrorResponse($message) {
        return response()->json(['success' => false, 'message' => $message], 200);
    }

    /**
     * Throw exception error
     * @param type $message
     * @param type $status
     * @return type
     */
    public function sendExceptionError($exception, $status = 400, $method = "undefined") {
        $log = [];
        $log["message"] = $exception->getMessage();
        $log["file"] = $exception->getFile();
        $log["line"] = $exception->getLine();
        \Log::info(print_r($log, true));
        $payload = ['message' => $exception->getMessage(), 'fv_server' => config("general.show_errors_on_app")];
        if (!empty($status) && in_array($status, [400, 401])) {
            $payload['error_type'] = "$status";
        }
        return response()->json($payload, $status);
    }

}
