<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tzsk\Collage\MakeCollage;
use App\Helpers\Media\FiveImage;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;

class TestCollageCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test_collage:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open("/home/ali/Videos/s3-vidoes/wat9.mov");
        $watermarkPath = "/home/ali/Videos/s3-vidoes/wat1.jpg";
        $format = new X264('libmp3lame', 'libx264');
        $video->filters()->watermark($watermarkPath, array(
            'position' => 'relative',
            'bottom' => 50,'right' => 50,
        ))->synchronize();
//        $video->synchronize();
        $format->setKiloBitrate(1000)->setAudioChannels(2)->setAudioKiloBitrate(256);
        $video->save($format, "/home/ali/Videos/s3-vidoes/wat10.mp4");
        return;

        $collage = new MakeCollage("gd");
        $start_time = $this->msec();

// Do something...

        $images = ['http://localhost/collage/images/3/1.jpg', 'http://localhost/collage/images/3/2.jpg',
            'http://localhost/collage/images/3/3.jpg', 'http://localhost/collage/images/3/4.jpg',
            'http://localhost/collage/images/3/5.jpg'];

        $image = $collage
                        ->with([5 => FiveImage::class])
                        ->make(400, 600)->padding(5)
                        ->background('#fff')->from($images, function($alignment) {

            $alignment->custom(); // Default, no need to have the Closure at all.
            // OR...
//                    $alignment->horizontal();
        });
//        $image = $collage
//                        
//                        ->make(600, 800)->padding(10)
//                        ->background('#fff')->from($images, function($alignment) {
//            $alignment->grid(); // Default, no need to have the Closure at all.
//            // OR...
////                    $alignment->horizontal();
//        });

        $image->save('public/bar.jpg');

        $end_time = $this->msec();

        echo 'Execution time: ' . ($end_time - $start_time) . ' milliseconds.';
//        print_r($image);
    }

    public function msec() {
        //$uTime = reset(explode(' ', microtime()));
        //$mSec = ceil($uTime * 1000); // also tried with round($uTime, 3);
//        list($usec, $sec) = explode(" ", microtime());
//        $mSec = ((float) $usec / 1000) + (float) $sec;
        $mt = explode(' ', microtime());
        return ((int) $mt[1]) * 1000 + ((int) round($mt[0] * 1000));
    }

}
