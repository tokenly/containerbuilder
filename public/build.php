<?php 

wlog("Begin build at ".date("Y-m-d H:i:s"));

print "Unimplemented...\n";



function wlog($text) {
    $output = "[".date("Y-m-d H:i:s")."] ".rtrim($text)."\n";
    $fd = fopen('/var/log/containerbuilder/build.log', 'a');
    fwrite($fd, $output);
}