<?php 

use App\Environment;
use App\Log;
use App\Queue;
use Exception;

require __DIR__.'/../vendor/autoload.php';
Environment::init(__DIR__.'/..');

try {
    
    Log::wlog("Begin queue build");

    // get the container name
    $container_name = isset($_GET['container']) ? $_GET['container'] : null;
    if (!$container_name) { throw new Exception("Container name not specified", 1); }

    $request = ['container' => $container_name, 'ts' => round(microtime(true) * 1000)];
    $queue = Queue::instance();
    $queue->push($request);

    print "Build queued\n";

} catch (Exception $e) {
    Log::logError($e);
    http_response_code(500);
    print "Build failed\n";
    exit();
}
