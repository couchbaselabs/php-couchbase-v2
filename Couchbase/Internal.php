<?php
/**
 * Interface to the Couchbase 2.0 HTTP API.
 *
 * @package Couchbase
 */

class Couchbase_Internal extends Couchbase_CouchDB
{
    /**
     * Deletes a database/bucket.
     *
     * @param string $name database/bucket name.
     * @param Couchbase $cb Couchbase client library object.
     */
    function deleteDb($name, $cb = null)
    {
        $result = $this->send("DELETE", "/pools/default/buckets/$name");
        if(empty($result)) { // some error deleting, don't wait.
            $this->waitForBucket($cb, Memcached::RES_UNKNOWN_READ_FAILURE);
        }
        return $result;
    }

    /**
     * Creates a database/bucket.
     *
     * @param string $name database/bucket name.
     * @param Couchbase $cb Couchbase client library object.
     */
    function createDb($name, $cb = null)
    {
        $result = $this->send(
            "POST", "/pools/default/buckets",
            "name=$name&ramQuotaMB=100&authType=sasl&replicaNumber=0&proxyPort=11215",
            "application/x-www-form-urlencoded"
        );
        $this->_waitForBucket($cb);
        return $result;
    }

    /**
     * Determins whether a database/bucket exists.
     *
     * Note for Jan: RENAME FOR CONSISTENCY YOU MORON. — Love, Jan.
     *
     * @param string $name database/bucket name.
     * @return boolean Whether the database/bucket exists.
     */
    function bucketExists($name)
    {
        $bucket_info = $this->send("GET", "/pools/default/buckets/$name");
        return $bucket_info != '["Unexpected server error, request logged."]'
            && $bucket_info != "Requested resource not found.";
    }

    /**
      * Utility function that waits for bucket creation.
      * Bucket creation is async, for the time being, we need to poll until
      * it is there.
      * @param Couchbase $cb Couchbase client library object.
      * @param constant Expected memcached result code.
      */
    function _waitForBucket($cb, $resultCode = Memcached::RES_SUCCESS)
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
