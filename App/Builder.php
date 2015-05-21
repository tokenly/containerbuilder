<?php

namespace App;

use App\Cmd;
use App\Log;
use Exception;

/**
* Builder
*/
class Builder
{


    public static function instance() {
        $instance = new Builder();
        return $instance;
    }

    public function __construct() {
    }


    public function build($container_name) {
        // pull docker-images
        $this->gitPullImages();

        // build
        $this->buildContainer($container_name);
    }

    public function gitPullImages() {
        Log::info("Updating docker images");
        Cmd::doCmd("git pull", "/app/docker-images");
    }

    public function buildContainer($container_name) {
        $REGISTRY_PREFIX = "tokenly";
        
        // initiate the build
        $buildpath = "/app/docker-images/".$container_name;
        if (!file_exists($buildpath)) { throw new Exception("Failed to find container $container_name", 1); }

        Log::info("Begin build of $container_name");
        Cmd::doCmd("docker build -t {$REGISTRY_PREFIX}/$container_name .", $buildpath);

    }

}

