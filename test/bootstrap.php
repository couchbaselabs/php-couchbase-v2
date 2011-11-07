<?php

function couchdb_autoload($class)
{
    require_once __DIR__ . "/../" . str_replace("_", "/", $class) . ".php";
}

spl_autoload_register('couchdb_autoload');
xdebug_start_trace("/tmp/couchdb");