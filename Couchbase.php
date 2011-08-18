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
     * @return bool
     */
    function addCouchbaseServer($host, $port = 11211)
    {
        return parent::addServer($host, $port);
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
}
