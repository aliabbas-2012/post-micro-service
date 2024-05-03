<?php

namespace App\Listeners;

use App\Models\FayvoActivity;
use App\Events\EngagementEvent;
use Carbon\Carbon;
use App\Models\PostComment;

class EngagementListener {

    /**
     * Handle the event.
     *
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(EngagementEvent $event) {
        try {
            $should_fire = false;

            $query = PostComment::select(\DB::raw("created_at"))->where("user_post_id", "=", $event->notification_model["object_id"])
                            ->where("user_id", "=", $event->notification_model["interactor_id"])
                            ->where("id", "<>", $event->notification_model["interaction_id"])
                            ->orderBy('created_at', 'desc')->limit(1);
            $activity_existance = $query->first();
            if (empty($activity_existance)) {
                $should_fire = true;
            } else if (Carbon::parse($activity_existance->created_at)->diffInSeconds(Carbon::now(), false) > config("general.interaction_trigger_interval")) {
                $should_fire = true;
            }
            if ($should_fire) {
                \Log::info('------> criteria matched ----> sending job----');
                $engagement_job = (new \App\Jobs\EngagementPushnotificationJob($event->notification_model))->delay(20);
                dispatch($engagement_job);
                return true;
            }
            \Log::info('------> criteria not matched ---->');

            return true;
        } catch (\Exception $ex) {
            return true;
        }
    }

}
