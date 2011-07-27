<?php
/**
 * Interface to the Couchbase 2.0 HTTP API.
 *
 * @package Couchbase
 */

class Couchbase_Internal extends Couchbase_CouchDB
{
    function deleteDb($name, $cb)
    {
        $result = $this->send("DELETE", "/pools/default/buckets/$name");
        if(empty($result)) { // some error deleting, don't wait.
            $this->waitForBucket($cb, Memcached::RES_UNKNOWN_READ_FAILURE);
        }
        return $result;
    }

    function createDb($name, $cb)
    {
        $result = $this->send(
            "POST", "/pools/default/buckets",
            "name=$name&ramQuotaMB=100&authType=sasl&replicaNumber=0&proxyPort=11215",
            "application/x-www-form-urlencoded"
        );
        $this->waitForBucket($cb);
        return $result;
    }

    /**
      * bucket creation is async, for the time being, we need to poll until
      * it is there.
      */
    function waitForBucket($cb, $resultCode = Memcached::RES_SUCCESS)
    {
        // var_dump("--waitForBucket");
        do {
            $cb->set("f", 1);
            usleep(500000); // 1/2 second
            // var_dump($cb->getResultMessage());
        } while($cb->getResultCode() !== $resultCode);
        $cb->delete("f");
        // var_dump("--done waiting");
    }
}