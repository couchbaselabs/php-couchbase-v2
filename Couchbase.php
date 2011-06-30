<?php
/**
 * Base Class
 */

require("Couchbase/CouchDB.php");
require("Couchbase/QueryResult.php");
require("Couchbase/QueryDefinition.php");

class Couchbase_MemcachedNotLoadedException extends Exception {}

if(!extension_loaded("memcached")) {
    throw(new Couchbase_MemcachedNotLoadedException);
}

class Couchbase extends Memcached
{

    var $queries = array();
    var $query_servers = array();
    var $default_bucket_name = "default";

    function __construct($id = null) {
        parent::__construct($id);
    }

    function addServer($host, $port, $weight = 0) {
        $this->query_server = array("host" => $host, "port" => 5984);
        $this->couchdb = new Couchbase_CouchDB("http://$host:5984/{$this->default_bucket_name}");
        return parent::addServer($host, $port, $weight);
    }

    function query($name, $options = array()) {
        list($group, $name) = $this->_parseQueryName($name);
        $result = $this->couchdb->view($group, $name, $options);
        return new Couchbase_QueryResult($result);
    }

    function addQuery($name, $query_definition) {
        list($group, $name) = $this->_parseQueryName($name);
        $this->queries[$group][$name] = $query_definition;
        $this->_updateGroup($group);
        return true;
    }

    function _updateGroup($group_name) {
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

    function _parseQueryName($name) {
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
