<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;

class AddFayvoTestUsersInEsIndexCommand extends Command {

    use \App\Traits\CommonTrait;

    public $es_index = "trending";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add_fayvo_test_users:es';

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
        $max_id = $this->getMaxEsId($client, $this->es_index, 'user');
//        $max_id = 0;

        $limit = 10000;
        $total_pages = ceil(User::where("archive", "=", false)
                        ->where("role_id", '<>', 1)->where("id", ">", $max_id)->count() / $limit);
        $offset = 0;
        $columns = ["id", "username", "full_name", "uid", "facebook_id",
            "is_live", "latitude", "longitude", "score", "created_at", "updated_at"
        ];
        $this->output->progressStart($total_pages);
        for ($i = 0; $i < $total_pages; $i++) {

            $records = User::select($columns)
                            ->where("archive", "=", false)
                            ->where("role_id", '<>', 1)
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

            $params['body'][] = $this->prepareCommandEsIndex($this->es_index, $model->id, 'u');
            $data = [
                'id' => (int) $model->id,
                'uid' => $model->uid,
                'subject_id' => !empty($model->facebook_id) ? $model->facebook_id : "",
                'name' => $model->username,
                'content' => $model->full_name,
                'is_live' => boolval($model->is_live),
                'score' => $model->score,
                "type" => ["name" => "user"],
                'created_at' => Carbon::parse($model->created_at)->format("Y-m-d\TH:i:s\Z"),
                'updated_at' => Carbon::parse($model->updated_at)->format("Y-m-d\TH:i:s\Z"),
            ];
            if (!empty($model->latitude) && !empty($model->longitude)) {
                $data["created_location"] = [
                    "lat" => (float) $model->latitude,
                    "lon" => (float) $model->longitude,
                ];
            }

            $params['body'][] = $data;
        }
        $responses = $client->bulk($params);

        return $responses;
    }

}
