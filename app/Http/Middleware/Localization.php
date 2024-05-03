<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Lang;

class Localization {

    private $sdks = ['aws-sdk-android', 'aws-sdk-ios', "postmanruntime"];
    private $appVersion = "appversion";

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  int                      $maxAttempts
     * @param  int                      $decayMinutes
     *
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $lang = $request->header('lang');
        \Log::info("---language request header------$lang");
        \Log::info("--- request user-agent------");
        \Log::info($request->header('user-agent'));
        // default language
        $this->setAPIVersion($request, $request->header('user-agent'));
        $locale = "en";
        if (!empty($lang) && in_array($lang, ["en", "ar"])) {
            $locale = strtolower($lang);
        } else {
            $locale = $this->processAgent(strtolower($request->header('user-agent')));
        }
        \Log::info("---language final header------$locale");
        // set application localization language
//        Lang::setLocale($locale);
        app('translator')->setLocale($locale);
        return $next($request);
    }

    private function processAgent($userAgent = "") {
        $locale = "en";

        if (!empty($userAgent) && strpos($userAgent, $this->appVersion) !== false) {

            foreach ($this->sdks as $key => $sdk) {

                if (strpos($userAgent, $sdk) !== false) {
                    $locale = $this->extractAppLang($userAgent, $sdk);
                    break;
                }
            }
        }
        return $locale;
    }

    private function extractAppLang($agent, $sdk) {
        $locale = "en";

        $agent = explode("$sdk/", $agent)[1];

        if ((strpos($agent, 'ar_') !== false) || (strpos($agent, 'ar,') !== false)) {
            $locale = "ar";
        }
        return $locale;
    }

    private function setAPIVersion($request, $userAgent) {
        \Log::info("--- Setting API VErsion ---");
        \Log::info($userAgent);
        $agent = strtolower($userAgent);
        preg_match("/apiversion\/(.*)/", $agent, $matches);
        if (!empty($matches[1])) {
            $apiVersion = $this->cutNum(filter_var($matches[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), 1); // float(55.35) 
            config(['general.base_app_version' => $apiVersion]);
            $request->apiVersion = $apiVersion;
        } else {
            $request->apiVersion = config("general.base_app_version");
        }
    }

    private function cutNum($num, $precision = 2) {
        return floatval(preg_replace("/\.([0-9]{1})[0-9]{0,99}/", ".$1", $num));
    }

}
