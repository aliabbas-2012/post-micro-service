<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Description of MoveArchivedPosts
 *
 * @author rizwan
 */
class MoveArchivedPosts extends Command {

    /**
     * php artisan remove_archive_posts:posts
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move_archive_posts:posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove archive posts';

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
        $this->process(0);
    }

    public function process($last_id = 0) {
        if ($posts = $this->getArchivePostsWithAssociations($last_id)) {
            $postsIds = $this->filterActivationPosts($posts);
            $this->activationIds = array_unique(array_merge($this->activationIds, $postsIds));
            return $this->process($this->last_id);
        }
        if (!empty($this->activationIds)) {
            \App\Models\UserPost::whereIn('id', $this->activationIds)->update(['archive' => 0, 'status' => 'A']);
            \App\Models\PostBox::whereIn('user_posts_id', $this->activationIds)->update(['archive' => 0, 'status' => 'A']);
        }
        echo "\n";
        echo "<pre>";
        print_r($this->activationIds);
        exit;
    }

    private function filterActivationPosts($posts) {
        $ids = [];
        foreach ($posts as $key => $post) {
            if ($post->archive == 1 && $post->status != 'D') {
                $ids[] = $post->id;
            }
            $this->last_id = $post->id;
        }
        return $ids;
    }

    private function getArchivePostsWithAssociations($last_id) {
        $sql = "select DISTINCT P.id,P.archive,P.status from user_posts P ,user_posts_boxes PB "
                . "where P.archive=1 and PB.user_posts_id=P.id and P.id>$last_id ORDER BY P.id ASC limit 500;";
        $data = \DB::select($sql);
        return $data;
    }

}
