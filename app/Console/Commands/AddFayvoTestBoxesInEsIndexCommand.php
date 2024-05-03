<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Box;
use Elasticsearch\ClientBuilder;

class AddFayvoTestBoxesInEsIndexCommand extends Command {

    use \App\Traits\CommonTrait;

    public $es_index = "fayvo_test";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add_fayvo_test_boxes:es';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Fayvo Test users in In ES index';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $client = $this->getEsClient(config("elastic_search.path"));
        $max_id = $this->getMaxEsId($client, $this->es_index, 'boxes');
//        $max_id = 0;

        $limit = 100000;
        $total_pages = ceil(Box::where("archive", "=", false)
                        ->where("id", ">", $max_id)->count() / $limit);
        $offset = 0;
        $columns = ["id", "name", "user_id", "status", "created_at", "updated_at"];
        $this->output->progressStart($total_pages);
        for ($i = 0; $i < $total_pages; $i++) {

            $records = Box::select($columns)
                            ->where("archive", "=", false)
                            ->where("id", ">", $max_id)
                            ->orderBy("id", "ASC")
                            ->limit($limit)->offset($offset)->get();



            $resp = $this->indexRecords($records, $client);

            $offset = $offset + $limit;
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

    /**
     * 
     * @param type $records
     * @param type $client
     * @return type
     */
    private function indexRecords($records, $client) {
        $params = [];
        foreach ($records as $model) {
            $params['body'][] = $this->prepareCommandEsIndex($this->es_index, $model->id, 'bx');
            $data = [
                'id' => (int) $model->id,
                'name' => $model->name,
                'subject_id' => $model->user_id,
                'status' => $model->status,
                "type" => ["name" => "boxes", "parent" => "u-" . $model->user_id],
                'created_at' => Carbon::parse($model->created_at)->format("Y-m-d\TH:i:s\Z"),
                'updated_at' => Carbon::parse($model->updated_at)->format("Y-m-d\TH:i:s\Z"),
            ];
            $params['body'][] = $data;
        }

        $responses = $client->bulk($params);

        return $responses;
    }

    private function getMaxEsId($client) {
        $params = [
            "index" => $this->es_index,
            "type" => "doc",
            "body" => [
                "size" => 0,
                "_source" => false,
                "query" => [
                    "term" => [
                        "type" => "boxes"
                    ]
                ],
                "aggs" => [
                    "max_id" => ["max" => ["field" => "id"]]
                ]
            ]
        ];

        $data = $client->search($params);

        if (!empty($data['aggregations']['max_id']["value"])) {
            return $data['aggregations']['max_id']['value'];
        }
        return 0;
    }

}
