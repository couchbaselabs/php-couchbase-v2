<?php
error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
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
     * @param string $host Couchbase hostname or IP address.
     * @param int $port TCP port number for Membase API.
     * @param int $couchport TCP port number for Couch API.
     * @param string $internal_host Couchbase admin API host name or IP.
     * @param $internal_port Couchbase admin API TCP port number.
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
        $this->_readDesignDocs();
        return parent::addServer($host, $port);
    }

    /**
     * Helper method to allow defining a new view programatically.
     *
     * @param Couchbase_ViewDefinition $view_definition View definition.
     * @return bool
     */
    function addView($view_definition)
    {
        $this->queries[$view_definition->ddoc_name]
            [$view_definition->name] = $view_definition;
        $this->_updateDesignDocument($view_definition->ddoc_name);
        $this->_waitForDesignDocUglyHack($view_definition->ddoc_name);
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

    /**
     * Returns a `Couchbase_View` specified by design document name and view
     * name.
     *
     * @param $ddoc_name Design doc name (without the leading "_design/")
     * @param $view_name Name of the view inside the design doc
     * @return Couchbase_View instance ready for querying
     */
    function getView($ddoc_name, $view_name)
    {
        if(!isset($this->queries[$ddoc_name][$view_name])) {
            return false;
        }

        $view = $this->queries[$ddoc_name][$view_name];
        $view->db = $this;
        return $view;
    }

    /**
     * Like `getView()` but used to access the built-in `_all_docs` view.
     *
     * @return Couchbase_AllDocsView
     */
    function getAllDocsView()
    {
        $allDocsView = new Couchbase_AllDocsView;
        $allDocsView->db = $this;
        return $allDocsView;
    }

    /**
     * Wrap the `touch()` method for php-memcached/libmemcached installations
     * that do not support it (< 1.7)
     *
     * @param string $key Key
     * @param integer $expriy Number of seconds until the item expires.
     * @return boolean Success or not.
     */
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
     * Utility function that reads all design docs and view definitions from
     * a bucket and makes them available for querying.
     *
     * Note: This happens on every instantiation of this class.
     *       This needs to be optimized in various ways:
     *        - Only load a view definition if asked for.
     *        - Cache a view definition locally (file, apc etc.)
     *          for a while to avoid even the on-demand lookups
     *          all the time.
     * @return void
     */
    function _readDesignDocs()
    {
        if(!$this->couchbase->bucketExists($this->default_bucket_name)) {
            return;
        }

        $view = $this->getAllDocsView();
        $ddocs = $view->getResultByRange("_design/", "_design0",
            array("include_docs" => true));

        if(!$ddocs || !$ddocs->rows) {
            return;
        }

        foreach($ddocs->rows AS $ddoc_row) {
            $ddoc = $ddoc_row->doc;
            $ddoc_name = str_replace("_design/", "", $ddoc->_id);
            if(isset($ddoc->views)) {
                foreach($ddoc->views AS $view_name => $definition) {
                    $view = new Couchbase_View($ddoc_name, $view_name);
                    $this->queries[$ddoc_name][$view_name] = $view;
                }
            }
        }
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
