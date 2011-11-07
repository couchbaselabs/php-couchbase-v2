<?php
require_once "Couchbase.php";
require_once "test/lib.php";

/**
 * Couchbase test class for PHPUnit.
 *
 * @package Couchbase
 */
class CouchbaseClusterTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->cb = new Couchbase;
        $this->cb->addCouchbaseServer($GLOBALS['COUCHBASE_SERVER'], $GLOBALS['COUCHBASE_MEMCACHE_PORT'], $GLOBALS['COUCHBASE_COUCHDB_PORT'], $GLOBALS['COUCHBASE_INTERNAL_HOST'], $GLOBALS['COUCHBASE_INTERNAL_PORT']);
        $this->cb->flush();
        // $this->cb->couchbase->deleteDb("default", $this->cb);
        $this->cb->couchbase->createDb("default", $this->cb);
        $this->lib = new Couchbase_Test_Lib($this->cb);
    }

    function tearDown()
    {
        $this->cb->flush();
        unset($this->cb);
    }

    function kill_node($node)
    {
        $cmd = "kill `ps ax | grep beam | grep n_$node | awk '{print $1}'`";
        shell_exec($cmd);
    }

    function test_basic_query()
    {
        $this->lib->prepare_docs();
        $this->lib->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $this->assertInstanceOf("Couchbase_View", $view);

        $result = $view->getResult();
        $this->assertInstanceOf("Couchbase_ViewResult", $result);

        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

    function test_a_b()
    {
        $this->cb->set("a", '{"a":1}');
        $this->cb->set("b", '{"a":2}');

        $view = new Couchbase_View("default", "cluster");
        $view->setMapFunction("function(doc) { emit(doc.a, 1); }");
        $this->cb->addView($view);

        sleep(10);

        $view = $this->cb->getView("default", "cluster");
        $result = $view->getResult();
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("1", $result->rows[0]->key);
        $this->assertEquals("2", $result->rows[1]->key);
    }

    function test_a_b_with_dead_node()
    {
        $this->cb->set("a", '{"a":1}');
        $this->cb->set("b", '{"a":2}');

        $view = new Couchbase_View("default", "cluster");
        $view->setMapFunction("function(doc) { emit(doc.a, 1); }");
        $this->cb->addView($view);

        sleep(10);
        $this->kill_node(1);

        $view = $this->cb->getView("default", "cluster");
        $result = $view->getResult();
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("1", $result->rows[0]->key);
        $this->assertEquals("2", $result->rows[1]->key);
    }
}
