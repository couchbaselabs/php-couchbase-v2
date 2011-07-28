<?php
/**
 * Couchbase Client
 *
 * This package implements the public API for the Couchbase Cluster.
 * See http://couchbase.org/ for more information.
 *
 * This client is designed to be compatible with Couchbase 2.0 and later.
 *
 * @author Jan Lehnardt <jan@couchbase.com>
 * @version 0.9.0
 * @package Couchbase
 * @license Apache 2.0, See LICENSE file.
 */

/**
 * Require dependent classes
 */
require("Couchbase/CouchDB.php");
require("Couchbase/Internal.php");
require("Couchbase/View.php");
require("Couchbase/ViewDefinition.php");
require("Couchbase/ViewResult.php");
require("Couchbase/ViewResultPaginator.php");

/**
 * Exception that gets thrown when the memcached extension is not available
 * @package Couchbase
 */
class Couchbase_MemcachedNotLoadedException extends Exception {}

if(!extension_loaded("memcached")) {
    throw(new Couchbase_MemcachedNotLoadedException);
}

/**
 * Couchbase
 *
 * This class implements the public Couchbase API. It extends the
 * php/memcached extension's object oriented interface and adds Couchbase 2.0
 * specific features.
 *
 * @package Couchbase
 */
class Couchbase extends Memcached
{

    /**
     * List of queries defined for this bucket/database keyed by `$group` and
     * `$name`.
     * @var array list of queries
     */
    var $queries = array();

    /**
     * Hostname and port for the CouchDB API inside Couchbase.
     *
     * @var array
     */
    var $query_server = array();

    /**
     * Default bucket name for the Couchbase Cluster.
     *
     * @var string bucket name
     */
    var $default_bucket_name = "default";

    /**
     * Add a server to the connection pool.
     *
     * @param string $host hostname or IP address
     * @param int $port TCP port number
     * @param int $weight relative wright for being selected from a pool
     * @return bool
     */
    function addCouchbaseServer($host, $port = 11211, $couchport = 5984,
        /* private*/ $internal_host = null, $internal_port = 8091 /* private end */)
    {
        if($internal_host === null) {
            $internal_host = $host;
        }

        $this->query_server = array("host" => $host, "port" => $couchport);
        $this->couchdb = new Couchbase_CouchDB("http://$host:$couchport/{$this->default_bucket_name}");
        $this->couchbase = new Couchbase_Internal("http://$internal_host:$internal_port/");
        return parent::addServer($host, $port);
    }

    /**
     * Helper method to allow defining a new view programatically.
     *
     * @param string $name View name.
     * @param Couchbase_ViewDefinition $query_definition View definition.
     * @return bool
     */
    function addView($ddoc_name, $view_name, $view_definition)
    {
        $view_definition->ddoc_name = $ddoc_name;
        $view_definition->name = $view_name;
        $this->queries[$ddoc_name][$view_name] = $view_definition;
        $this->_updateDesignDocument($ddoc_name);
        $this->_waitForDesignDocUglyHack($ddoc_name);
        return true;
    }

    // wait for ddocs to be all synced to all buckets and whatnot
    // the server should do the wait for me or send me a notification
    function _waitForDesignDocUglyHack($ddoc_name)
    {
        // var_dump("--waitForDdoc");
        sleep(4);
        // do {
        //     usleep(300);
        //     $result = $this->couchdb->view("default", $ddoc_name);
        //     var_dump($result);
        //     $json_result = json_decode($result);
        // } while(isset($json_result->error) && ($json_result->error == "not_found"));
        // var_dump("--done waitForDdoc");
    }

    function getView($ddoc_name, $view_name)
    {
        $view = $this->queries[$ddoc_name][$view_name];
        $view->db = $this;
        return $view;
    }

    function touch($key, $expriy = 0)
    {
        if(!method_exists("Memcached", "touch")) {
            trigger_error(E_WARNING,
                "Your memcached extension does not support the touch() method.");
            return false;
        }
        return parent::touch($key, $expiry);
    }

    /**
     * Utility method, updates a design document on the server
     *
     * @param string $ddoc_name design doc to update.
     * @return void
     */
    function _updateDesignDocument($ddoc_name)
    {
        $ddoc_definition = $this->queries[$ddoc_name];
        $ddoc = new stdClass;
        $ddoc->_id = "_design/$ddoc_name";
        foreach($ddoc_definition AS $name => $definition) {
            // why does PHP lack "undefined"?
            $view_def = new stdClass;
            if($definition->view_definition->map) {
                $view_def->map = $definition->view_definition->map;
            }

            if($definition->view_definition->reduce) {
                $view_def->reduce = $definition->view_definition->reduce;
            }

            if($definition->view_definition->options) {
                $view_def->options = $definition->view_definition->options;
            }

            $ddoc->views[$name] = $view_def;
        }

        // get _rev
        $old = json_decode($this->couchdb->open("_design/$ddoc_name"));
        if(!isset($old->error)) {
            $ddoc->_rev = $old->_rev;
        }

        $ddoc_json = json_encode($ddoc);
        $this->couchdb->saveDoc($ddoc_json);
    }
}
