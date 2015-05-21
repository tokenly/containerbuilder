#!/usr/bin/env php
<?php 

use App\Builder;
use App\Deployer;
use App\Environment;
use App\Log;
use App\Queue;

require __DIR__.'/../vendor/autoload.php';
Environment::init(__DIR__.'/..');

$builder = Builder::instance();
$deployer = Deployer::instance();

$queue = Queue::instance();
Log::info('BEGIN build queue');
$last_update = time();

while (true) {
    $job = $queue->pop();
    if ($job) {
        $data = $queue->decodeJob($job);
        Log::debug("running job: ".json_encode($data, 192));

        try {

            // $builder->build($data['container']);

            $deployer->deploy($data['container']);

            $queue->delete($job);
        } catch (Exception $e) {
            Log::logError($e);

            // bury this job for 5 seconds
            $queue->release($job, 5);

            // sleep for 1 second
            usleep(1000000);
        }

    }

    // sleep for 25 ms
    usleep(25000);

    if (time() - $last_update > 300) {
        Log::info('build queue still running...');
        $last_update = time();
    }
}


