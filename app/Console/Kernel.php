<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\DeleteArchivedPostsEs::class,
        Commands\DeleteArchivePostAssociations::class,
        Commands\UpdateUserPostsLocationCommand::class,
        Commands\UpdateUserPostsScoreCommand::class,
        Commands\UpdateUserPostsScoreAggCommand::class,
        //new test
        Commands\TestCollageCommand::class,
        Commands\AddPostsInEsCommand::class,
        Commands\AddPostsBoxInEsCommand::class,
        Commands\AddFayvoTestUsersInEsIndexCommand::class,
        Commands\AddFayvoTestBoxesInEsIndexCommand::class,
        Commands\AddFayvoTestBlockInEsIndexCommand::class,
        Commands\AddFayvoTestFriendInEsIndexCommand::class,
        Commands\TestEloquentCommand::class,
        Commands\UpdateBucketColumn::class,
        Commands\TestDbCommand::class,
        Commands\FindAndMoveArchivePosts::class,
        Commands\ActivateDBArchivePostsES::class,
        Commands\MoveArchivedPosts::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        //
    }

}
