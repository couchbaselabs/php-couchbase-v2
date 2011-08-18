<?php
require_once "Couchbase.php";
require_once "PHPUnit/Framework/TestCase.php";

/**
 * Couchbase test class for PHPUnit.
 *
 * @package Couchbase
 */
class CouchbaseTest extends PHPUnit_Framework_TestCase
{
    function setUp($flush = true)
    {
        $this->cb = new Couchbase;
        $this->cb->addCouchbaseServer("localhost");
        if($flush) {
            $this->cb->flush();
        }
    }

    function tearDown()
    {
        $this->cb->flush();
        unset($this->cb);
    }

    function test_instantiation()
    {
        $this->assertInstanceOf("Couchbase", $this->cb);
    }

    function test_get_set()
    {
      $this->cb->set("a", "b");
      $res = $this->cb->get("a");
      $this->assertEquals("b", $res);
    }
}
