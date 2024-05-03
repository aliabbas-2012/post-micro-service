<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\EsQueries;

/**
 * Description of BaseEsQuery
 *
 * @author rizwan
 */
class BaseEsQuery {

    /**
     * Get Es query index
     * @param type $index
     * @param type $doc_type
     * @param type $body
     * @return type
     */
    public function getEsQueryIndex($index, $type, $body = [], $lazy = false) {
        $query = [
            'index' => $index,
            'type' => $type,
            'body' => $body
        ];
        if ($lazy) {
            $query['client'] = [
                'future' => 'lazy'
            ];
        }
        return $query;
    }

}
