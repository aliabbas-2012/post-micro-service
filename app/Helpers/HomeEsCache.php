<?php

namespace App\Helpers;

use Elasticsearch\ClientBuilder;
use App\Http\EsQueries\HomeEsQuery;

/**
 * Purpose of this class to make ES Cache and Query
 * @author ali
 */
class HomeEsCache {

    private static $instance = FALSE, $esClient = false;

    private function __construct() {
        
    }

    static function getInstance($esClient = false) {
        if (FALSE == self::$instance) {
            self::$instance = new HomeEsCache();
        }
        //SET ES client
        if (FALSE == self::$instance) {
            self::$esClient = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
        } else {
            self::$esClient = $esClient;
        }
        return self::$instance;
    }

    public function bulkCache($request) {
        self::$esClient->bulk($request);
    }

    /**
     * 
     * @param type $inputs
     */
    public function performEsQuery($inputs, $user_id) {
        $homeEsQuery = new HomeEsQuery($user_id);
        $body = $homeEsQuery->prepareHomeQuery($inputs);
        if ($data = self::$esClient->search($body)) {
            return $data["hits"]["total"] > 0 ? array_column($data["hits"]["hits"], "_source") : [];
        }
        return false;
    }

}
