<?php

namespace App;

use App\Cmd;
use App\Consul;
use App\Log;
use Exception;

/**
* Deployer
*/
class Deployer
{


    public static function instance() {
        $instance = new Deployer();
        return $instance;
    }

    public function __construct() {
    }


    public function deploy($container_name, $config = []) {
        $config = array_merge([
            'strategy' => 'standard',
        ],$config);
        Log::info("deploying image $container_name");

        switch ($config['strategy']) {
            case 'webalt':
                $this->deployUsingWebAltStrategy($container_name);
                break;
            
            default:
                $this->deployUsingStandardStrategy($container_name);
                break;
        }
        
        Log::info("deploy $container_name finished");
    }


    public function deployUsingStandardStrategy($container_name) {
        throw new Exception("Unimplemented", 1);
        
    }

    public function deployUsingWebAltStrategy($container_name) {
        $HAPROXY_HEALTH_WAIT  = 8;
        $HAPROXY_RESTART_WAIT = 14;

        $container_name_alt = $container_name."alt";

        $consul = Consul::instance();

        // start the alt container
        Log::info("starting {$container_name_alt}");
        Cmd::doCmd("docker-compose up -d {$container_name_alt}", "/app/dctokenly");
        $this->sleepWithLog($HAPROXY_RESTART_WAIT, "waiting {$HAPROXY_RESTART_WAIT} haproxy to restart");


        // mark the alt container as active
        //   and the main container as inactive
        Log::info("Changing consul statuses $container_name_alt => UP, $container_name => DOWN");
        $consul->healthUp($container_name_alt);
        $this->sleepWithLog($HAPROXY_HEALTH_WAIT, "waiting {$HAPROXY_HEALTH_WAIT} seconds for haproxy $container_name_alt UP");
        $consul->healthDown($container_name);
        $this->sleepWithLog($HAPROXY_HEALTH_WAIT, "waiting {$HAPROXY_HEALTH_WAIT} seconds for haproxy $container_name DOWN");


        // restart the main container
        Log::info("restarting {$container_name}");
        Cmd::doCmd("docker-compose up -d {$container_name}", "/app/dctokenly");
        $this->sleepWithLog($HAPROXY_RESTART_WAIT, "waiting {$HAPROXY_RESTART_WAIT} haproxy to restart");


        // change consul status
        Log::info("Changing consul statuses $container_name => UP, $container_name_alt => DOWN");
        $consul->healthUp($container_name);
        $this->sleepWithLog($HAPROXY_HEALTH_WAIT, "waiting {$HAPROXY_HEALTH_WAIT} seconds for haproxy $container_name UP");
        $consul->healthDown($container_name_alt);
        $this->sleepWithLog($HAPROXY_HEALTH_WAIT, "waiting {$HAPROXY_HEALTH_WAIT} seconds for haproxy $container_name_alt DOWN");


        // stop the alt container
        Log::info("stopping {$container_name}");
        Cmd::doCmd("docker-compose stop {$container_name_alt}", "/app/dctokenly");
        Log::info("removing {$container_name}");
        Cmd::doCmd("docker-compose rm -f {$container_name_alt}", "/app/dctokenly");
        $this->sleepWithLog($HAPROXY_RESTART_WAIT, "waiting {$HAPROXY_RESTART_WAIT} haproxy to restart");

        // done!
    }


    protected function sleepWithLog($time, $msg=null) {
        if ($msg === null) { $msg = "Sleeping for $time seconds."; }
        Log::info($msg);
        sleep($time);
    }

}

