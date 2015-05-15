<?php 

$REGISTRY_PREFIX = "registry.tokenly.vbox:5000";

try {
    wlog("Begin build");

    // pull docker-images
    wlog("Updating docker images");
    doCmd("git pull", "/app/docker-images");

    // get the container name
    $container_name = isset($_GET['container']) ? $_GET['container'] : null;
    if (!$container_name) { throw new Exception("Container name not specified", 1); }

    // initiate the build
    $buildpath = "/app/docker-images/".$container_name;
    if (!file_exists($buildpath)) { throw new Exception("Failed to find container $container_name", 1); }
    wlog("Begin build of $container_name");
    doCmd("docker build -t {$REGISTRY_PREFIX}/$container_name .", $buildpath);


    // deploy
    


} catch (Exception $e) {
    wlog("Error at ".$e->getFile().", line ".$e->getLine().": ".$e->getMessage());
    http_response_code(500);
    print "Build failed\n";
    exit();
}

wlog("Build complete");
print "Build succeeded\n";




function wlog($text) {
    $output = "[".date("Y-m-d H:i:s")."] ".rtrim($text)."\n";
    $fd = fopen('/var/log/containerbuilder/build.log', 'a');
    fwrite($fd, $output);
}


function doCmd($cmd, $cwd=null, $allow_exception=false) {
  try {
    $old_cwd = null;
    if ($cwd !== null) { $old_cwd = chdir($cwd); }

    $return = array();
    wlog(($cwd ? '['.$cwd.' #]' : '#').' '.$cmd);
    exec($cmd, $return, $return_code);
    $output = join("\n",$return);
    if (strlen($output)) { wlog($output); }

    if ($old_cwd !== null) { chdir($old_cwd); }

    if ($return_code) { throw new Exception("Command failed with code $return_code".(strlen(trim($output)) > 0 ? "\n".$output : ''), $return_code); }

    return $output;
  } catch (Exception $e) {
    if ($allow_exception) {
        wlog("Error: ".$e->getMessage());
        return null;
    }
    throw $e;
  }
}
