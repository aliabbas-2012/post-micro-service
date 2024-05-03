<?php

namespace App\Observers;

use Carbon\Carbon;
use Elasticsearch\ClientBuilder;

class BaseObserver {

//    public $fields = [];
//    public $indexType = "trending";
//    public $documentType = null;
//    public $client = null;
//    public $parentMapping = null;
//    public $geoLocFields = [];
//    public $childRelations = [];
//    public $documentTypes = [];
//
//    public function __construct() {
//        $this->client = ClientBuilder::create()->setHosts([config("elastic_search.path")])->build();
//    }
//
//    /**
//     * After record created
//     * @param type $model
//     */
//    public function created($model) {
//        $this->syncToElastic("create", $model);
//    }
//
//    public function updated($model) {
//        $this->syncToElastic("update", $model);
//    }
//
//    public function deleted($model) {
//        $this->syncToElastic("delete", $model);
//    }
//
//    /**
//     * prepare parent data
//     * @param type $data
//     * @param type $relation
//     * @return type
//     */
//    public function detectParents($data, $relation = null) {
//        $mapping = null;
//
//        if (!empty($this->parentMapping) && $this->parentMapping != null) {
//
//            if (isset($this->parentMapping['fields']) && $relation != null) {
//                $mapping['parent'] = $this->parentMapping['fields'][$relation]['field']['name'];
//                $mapping['name'] = $relation;
//            } else {
//                $mapping = [];
//                $parentId = isset($this->parentMapping['field']) ? $this->parentMapping['field'] : '';
//                if ($parentId != null && !empty($parentId)) {
//                    $mapping['parent'] = $this->parentMapping['field']['name'];
//                    $mapping['name'] = $this->documentType;
//                }
//            }
//        }
//
//        return $mapping;
//    }
//
//    /**
//     * prepare elastic data
//     * @param type $model
//     * @param type $prefix
//     * @param type $relation
//     * @param type $field_prefix
//     * @return type
//     */
//    public function prepareData($model, $prefix = "", $relation = null, $field_prefix = []) {
//        $dataArr = [];
//
//        $data = $model->toArray();
//        if (isset($data['id'])) {
//            $dataArr['db_id'] = $data['id'];
//            $data['id'] = $this->prepareId($data["id"], $prefix);
//        }
//        if (!empty($this->fields) && $this->fields != null) {
//            foreach ($this->fields as $key => $field) {
//                $dataArr[$field] = isset($data[$key]) ? $data[$key] : '';
//            }
//        }
//        //setting GEO location fields
//        if (!empty($this->geoLocFields)) {
//            foreach ($this->geoLocFields as $locField => $location) {
//                if (isset($data[$location['latitude']]) && isset($data[$location['longitude']]) && !empty($data[$location['latitude']]) && !empty($data[$location['longitude']])) {
//                    $dataArr[$locField]['lat'] = (float) number_format($data[$location['latitude']], 2, '.', '');
//                    $dataArr[$locField]['lon'] = (float) number_format($data[$location['longitude']], 2, '.', '');
//                } else {
//                    $dataArr[$locField]['lat'] = (float) number_format(config('general.saudi_location.lat'), 2, '.', '');
//                    $dataArr[$locField]['lon'] = (float) number_format(config('general.saudi_location.lon'), 2, '.', '');
//                }
//            }
//        }
//
//        //parent detection...
//        $data = $dataArr; //updating data set as i believe we are done with the process
//
//        if ($parentData = $this->detectParents($data, $relation)) {
//
//            if (isset($data[$parentData['parent']])) {
//
//                $is_parent_prefix = true;
//                if (isset($data['object_id']) && isset($field_prefix['object_id'])) {
//                    $data['object_id'] = $this->prepareId($data['object_id'], $field_prefix['object_id']);
//                }
//
//                if (isset($data['user_id']) && isset($field_prefix['user_id'])) {
//                    $data['user_id'] = $this->prepareId($data['user_id'], $field_prefix['user_id']);
//                } else {
//                    
//                }
//
//                if (!isset($field_prefix['user_id']) && !isset($field_prefix['object_id'])) {
//                    $parentData['parent'] = $this->prepareId($data[$parentData['parent']], $this->parentMapping['prefix']);
//                } else {
//                    $parentData['parent'] = $data[$parentData['parent']];
//                }
//            }
//            $data['type'] = $parentData;
//        }//parent assignment
//        else {
//            $data['type'] = ["name" => $this->documentType];
//        }
//
//
//        if (isset($data['created_at']) && !empty(isset($data['created_at']))) {
//            $data['created_at'] = Carbon::parse($data['created_at'])->format("Y-m-d\TH:i:s\Z");
//        }
//        if (isset($data['updated_at']) && !empty(isset($data['updated_at']))) {
//            $data['updated_at'] = Carbon::parse($data['updated_at'])->format("Y-m-d\TH:i:s\Z");
//        }
//        return $data;
//    }
//
//    /**
//     * synch data toe elastic search
//     * @param type $mode
//     * @param type $model
//     * @return type
//     */
//    public function syncToElastic($mode, $model) {
//
//        if ($mode == 'create' || $mode == 'update') {
//            return self::saveData($model);
//        } else if ($mode == "delete") {
//            $this->deleteFromElasticSearch($model);
//        }
//    }
//
//    public function saving($model) {
//        return $model;
//    }
//
//    public function saved($model) {
//        return $model;
//    }
//
//    public function prepareId($id, $prefix) {
//        return $prefix . "-" . $id;
//    }
//
//    /**
//     * create record in elastic search
//     * @param type $model
//     * @return type
//     */
//    public function saveData($model) {
//        //we consider, we will always have the ID for sync
//        if (!isset($model->id))
//            throw new Exception('Unable to process this request, ID is missing!');
//
//        try {
//            $params = [];
//
//            if (isset($this->parentMapping['fields']) && !empty($this->parentMapping['fields'])) {
//                $params = $this->prepareBulkDataArray($model);
//            } else if (!empty($this->fields)) {
//                $params = $this->prepareBulkSingleDataArray($model);
//            } else {
//                return false;
//            }
//            $resp = $this->client->bulk($params);
//            return isset($resp['status']) && $resp['status'] == 200 ? true : false;
//        } catch (Exception $ex) {
//            return false;
//        }
//    }
//
//    /**
//     * prepare multiple bulk data array
//     * @param type $model
//     * @return array
//     */
//    protected function prepareBulkDataArray($model) {
//        $params = [];
//        if (isset($this->parentMapping['fields']) && !empty($this->parentMapping['fields'])) {
//            foreach ($this->parentMapping['fields'] as $relation => $info) {
//                $params['body'][] = ['index' => ['_index' => $this->indexType, '_type' => 'doc', '_id' => $this->prepareId($model->id, $info['prefix']), "routing" => 1]];
//                $params['body'][] = $this->prepareData($model, $info['prefix'], $relation, $info['prefixes']);
//            }
//        }
//        return $params;
//    }
//
//    /**
//     * prepare bulk data array
//     * @param type $model
//     * @return array
//     */
//    protected function prepareBulkSingleDataArray($model) {
//        $prefixes = !empty($this->parentMapping['prefixes']) ? $this->parentMapping['prefixes'] : array();
//        $params['body'] = [
//            ['index' => ['_index' => $this->indexType, '_type' => 'doc', '_id' => $this->prepareId($model->id, $this->modalPrefix), "routing" => 1]],
//            $this->prepareData($model, $this->modalPrefix, null, $prefixes),
//        ];
//        return $params;
//    }
//
//    /**
//     * delete data from Elastic search
//     * @param type $model
//     * @return type
//     */
//    public function deleteFromElasticSearch($model) {
//        $params = [
//            "index" => $this->indexType,
//            "type" => "doc",
//            "body" => [
//            ]
//        ];
//        if (isset($this->childRelations) && !empty($this->childRelations)) {
//            $params['body'] = [
//                "query" => [
//                    "bool" => [
//                        "should" => [
//                        ]
//                    ]
//                ]
//            ];
//            foreach ($this->childRelations as $relation => $prefix) {
//                if ($relation == "nested") {
//                    foreach ($prefix as $nestedKey => $nested) {
//                        if ($sub_query = $this->prepareNestedSubQuery($nestedKey, $nested, $model)) {
//                            if (count($sub_query) == 1) {
//                                $params['body']['query']['bool']['should'][] = $sub_query;
//                            } else {
//                                $params['body']['query']['bool']['should'] = array_merge($params['body']['query']['bool']['should'], $sub_query);
//                            }
//                        }
//                    }
//                } else {
//                    $params['body']['query']['bool']['should'][] = $this->prepareSubQuery($model, $relation, $prefix);
//                }
//            }
//        } else if (isset($this->documentTypes) && !empty($this->documentTypes)) {
//
//            $params['body'] = [
//                "query" => [
//                    "bool" => [
//                        "should" => [
//                        ]
//                    ]
//                ]
//            ];
//            foreach ($this->documentTypes as $type => $prefix) {
//                $params['body']['query']['bool']['should'][] = $this->prepareSubQuery($model, $type, $prefix);
//            }
//        }
//
//        if (!empty($params['body'])) {
//            $params['body']['conflicts'] = 'proceed';
//            $resp = $this->client->deleteByQuery($params);
//
//            return isset($resp['deleted']) & $resp['deleted'] > 0 ? true : false;
//        }
//
//        return false;
//    }
//
//    /**
//     * 
//     * @param type $model
//     * @param type $relation
//     * @param type $prefix
//     * @return type
//     */
//    private function prepareSubQuery($model, $relation, $prefix) {
//        return $sub_query = [
//            "bool" => [
//                "must" => [
//                    [
//                        "term" => [
//                            "id" => $this->prepareId($model->id, $prefix)
//                        ]
//                    ],
//                    [
//                        "term" => [
//                            "type" => $relation
//                        ]
//                    ]
//                ]
//            ]
//        ];
//    }
//
//    /**
//     * Elastic Search Nested level delete sub query
//     * @param type $nestedKey
//     * @param type $nested
//     * @param type $model
//     */
//    private function prepareNestedSubQuery($nestedKey, $nested, $model) {
//        $sub_query = [];
//        if ($nestedKey == "children") {
//            foreach ($nested as $relation_key => $relation) {
//                $sub_query2 = [
//                    "bool" => [
//                        "must" => [
//                            [
//                                "term" => [
//                                    "type" => $relation_key
//                                ]
//                            ],
//                            [
//                                "has_parent" => [
//                                    "parent_type" => $relation['parent'],
//                                    "query" => [
//                                        "bool" => [
//                                            "must" => [
//                                                [
//                                                    "term" => [
//                                                        $relation['field'] => $this->prepareId($model->id, $relation['prefix'])
//                                                    ]
//                                                ]
//                                            ]
//                                        ]
//                                    ]
//                                ]
//                            ]
//                        ]
//                    ]
//                ];
//                array_push($sub_query, $sub_query2);
//            }
//        } else {
//            $sub_query = [
//                "bool" => [
//                    "must" => [
//                        [
//                            "term" => [
//                                $nested['key'] => $this->prepareId($model->id, $nested['prefix'])
//                            ]
//                        ],
//                        [
//                            "term" => [
//                                "type" => $nestedKey
//                            ]
//                        ]
//                    ]
//                ]
//            ];
//        }
//
//        return $sub_query;
//    }

}
