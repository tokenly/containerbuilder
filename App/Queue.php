<?php

namespace App;

use Exception;
use Pheanstalk\Pheanstalk;

/**
* Queue
*/
class Queue
{

    static $INSTANCE;

    protected $pheanstalk = null;

    public static function instance() {
        if (self::$INSTANCE === null) {
            self::$INSTANCE = new Queue();
        }
        return self::$INSTANCE;
    }


    public function __call($method, $args) {
        return call_user_func_array([$this->pheanstalk, $method], $args);
    }

    public function push($payload) {
        return $this->pheanstalk->put(json_encode($payload));
    }

    public function pop($timeout=2) {
        return $this->pheanstalk->reserve($timeout);
    }

    public function decodeJob($job) {
        return json_decode($job->getData(), true);
    }

    public function release($job, $delay=0, $priority=null) {
        if ($priority === null) { $priority = Pheanstalk::DEFAULT_PRIORITY; }
        return $this->pheanstalk->release($job, $priority, $delay);
    }

    protected function __construct() {
        $this->pheanstalk = new Pheanstalk(getenv('BEANSTALK_HOST'), getenv('BEANSTALK_PORT'));
        $this->pheanstalk->useTube('build');
        $this->pheanstalk->watch('build');
    }



}

