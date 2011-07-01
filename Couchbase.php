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
 * @version 0.0.1
 * @package Couchbase
 * @license Apache 2.0, See LICENSE file.
 */

/*
  TODO: versioning check to make sure we work with a couchbase-capable
        memcached extension.
*/

/**
 * Require dependent classes
 */
require("Couchbase/CouchDB.php");
require("Couchbase/QueryResult.php");
require("Couchbase/QueryDefinition.php");

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
    function addServer($host, $port, $weight = 0)
    {
        $this->query_server = array("host" => $host, "port" => 5984);
        $this->couchdb = new Couchbase_CouchDB("http://$host:5984/{$this->default_bucket_name}");
        return parent::addServer($host, $port, $weight);
    }

    /**
     * Request a query result.
     *
     * @param string $name Name of the query. Optionally with a group/
     *        prefix. The default prefix is default/
     * @param array $options Associative array of query options that are equivalent to the CouchDB query options.
     * @return Couchbase_QueryResult
     */
    function query($name, $options = array())
    {
        list($group, $name) = $this->_parseQueryName($name);
        $result = $this->couchdb->view($group, $name, $options);
        return new Couchbase_QueryResult($result);
    }

    /**
     * Helper method to allow defining a new query programatically.
     *
     * @param string $name query name.
     * @param Couchbase_QueryDefinition $query_definition Queyr definition.
     * @return bool
     */
    function addQuery($name, $query_definition)
    {
        list($group, $name) = $this->_parseQueryName($name);
        $this->queries[$group][$name] = $query_definition;
        $this->_updateGroup($group);
        return true;
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
     * Utility method, updates a view group on the server
     *
     * @param string $group_name group to update.
     * @return void
     */
    function _updateGroup($group_name)
    {
        $group = $this->queries[$group_name];
        $ddoc = new stdClass;
        $ddoc->_id = "_design/$group_name";
        foreach($group AS $name => $definition) {
            $ddoc->views[$name] = $definition;
        }

        // get _rev
        $old = json_decode($this->couchdb->open("_design/$group_name"));
        if(!$old->error) {
            $ddoc->_rev = $old->_rev;
        }

        $ddoc_json = json_encode($ddoc);
        $this->couchdb->saveDoc($ddoc_json);
    }

    /**
     * Utility method, parses a query name.
     *
     * @param string $name query name, with optional group/ prefix.
     * @return array ($groupname = "default", $queryname)
     */
    function _parseQueryName($name)
    {
        $parts = split("/", $name);
        if(count($parts) == 1) {
            $group = "default";
            $query = $parts[0];
        } else {
            $group = $parts[0];
            $query = $parts[1];
        }
        return array($group, $query);
    }
}
