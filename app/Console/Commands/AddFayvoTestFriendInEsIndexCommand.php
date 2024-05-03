<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Friend;
use Elasticsearch\ClientBuilder;

class AddFayvoTestFriendInEsIndexCommand extends Command {

    use \App\Traits\CommonTrait;
    public $es_index = "fayvo_test";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add_fayvo_test_friend:es';

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
        $max_id = $this->getMaxEsId($client, $this->es_index, 'followings');
//        $max_id = 0;

        $limit = 100000;
        $total_pages = ceil(Friend::where("id", ">", $max_id)->count() / $limit);
        $offset = 0;
        $columns = ["id", "follower_id", "following_id", "status", "created_at"];
        $this->output->progressStart($total_pages);
        for ($i = 0; $i < $total_pages; $i++) {

            $records = Friend::select($columns)
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
            $params['body'][] = $this->prepareCommandEsIndex($this->es_index, $model->id, 'fg');

            $params['body'][] = $this->getData($model, "followings", "follower_id");
            //--
            $params['body'][] = $this->prepareCommandEsIndex($this->es_index, $model->id, 'fr');

            $params['body'][] = $this->getData($model, "followers", "following_id");
            
        }

        $responses = $client->bulk($params);

        return $responses;
    }

    private function getData($model, $type, $parent_field) {
        return [
            'id' => (int) $model->id,
            'subject_id' => $model->follower_id,
            'object_id' => $model->following_id,
            'status' => $model->status,
            "type" => ["name" => $type, "parent" => "u-" . $model->$parent_field],
            'created_at' => Carbon::parse($model->created_at)->format("Y-m-d\TH:i:s\Z"),
            'updated_at' => Carbon::parse($model->updated_at)->format("Y-m-d\TH:i:s\Z"),
        ];
    }

}
