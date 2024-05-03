<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BaseCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $esIndex = 'trending';

}
