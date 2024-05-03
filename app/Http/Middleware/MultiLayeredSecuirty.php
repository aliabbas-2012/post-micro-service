<?php

namespace App\Http\Middleware;

use Closure;

class MultiLayeredSecuirty {

    use \App\Traits\CommonTrait;
    use \App\Traits\MessageTrait;

    protected $local_cleint = 'PostmanRuntime';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        try {

            $user_agent = $request->header('user-agent');
            \Log::info("--- request user-agent ---");
            \Log::info($user_agent);
            \Log::info("--- internal microservices ---");
            \Log::info(print_r(config("general.internal_services"), true));
            if (in_array($user_agent, config("general.internal_services"))) {
                return $next($request);
            } elseif (strpos($user_agent, $this->local_cleint) !== false) {
                return $this->handlePostmanRequest($request, $next);
            } else {
                return $this->handleApiGatewayRequest($request, $next, $user_agent);
            }
        } catch (\Exception $ex) {
            \Log::info("----- Exception filed here ------");
            \Log::info($ex->getMessage() . "on " . $ex->getFile() . "in " . $ex->getFile());
            \Log::info("----- Exception ends here ------");
            return response()->json((['status' => 401, 'message' => $this->errors['unauthorized'], 'fv_server' => config("general.show_errors_on_app")]), 401);
        }
    }

    /**
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handlePostmanRequest($request, Closure $next) {
        return $next($request); // For testing
        $dev_ips = array_filter(array_merge(explode(",", env("DEV_IPS")), config("general.white_list_ips")));
        if (in_array($request->header("remote-ip"), $dev_ips)) {
            return $next($request);
        } else if (in_array($this->getClientIp(), config("general.white_list_ips"))) {
            return $next($request);
        } else {
            return response()->json((['status' => 401, 'message' => $this->errors['unauthorized'], 'fv_server' => config("general.show_errors_on_app")]), 401);
        }
    }

    /**
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handleApiGatewayRequest($request, Closure $next, $user_agent) {


        if (!empty($user_agent) || $user_agent !== null || $user_agent !== '') {
            $sdk_verification = $this->verifySDKIdentity($user_agent);
            \Log::info("-- SDK verification result----$sdk_verification");
            if (!$sdk_verification) {
                \Log::info("-- authorization failed");
                return response()->json((['status' => 401, 'message' => $this->errors['unauthorized'], 'fv_server' => config("general.show_errors_on_app")]), 401);
            } else {
                \Log::info("-- authorization allowed ---");
                return $next($request);
            }
        } else {
            \Log::info("-- user-agent condition failed ---");
            return response()->json((['status' => 401, 'message' => $this->errors['unauthorized'], 'fv_server' => config("general.show_errors_on_app")]), 401);
        }
    }

}
