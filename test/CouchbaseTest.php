<?php
require_once "Couchbase.php";
require_once "PHPUnit/Framework/TestCase.php";

class CouchbaseTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->cb = new Couchbase;
        $this->cb->addServer("localhost", 11211);
        $this->cb->couchdb->deleteDb("default");
        $this->cb->couchdb->createDb("default");
    }

    function tearDown()
    {
        unset($this->cb);
    }

    function prepare_docs()
    {
        $doc = new stdClass;
        $doc->name = "Simon";
        $this->cb->couchdb->saveDoc(json_encode($doc));

        $doc = new stdClass;
        $doc->name = "Ben";
        $this->cb->couchdb->saveDoc(json_encode($doc));

        $doc = new stdClass;
        $doc->name = "James";
        $this->cb->couchdb->saveDoc(json_encode($doc));
    }

    function prepare_ddoc($with_reduce = false)
    {
        $map_fun = <<<EOC_JS
        function(doc) {
            if(doc.name) {
                emit(doc.name, 1);
            }
        }
EOC_JS;

        $query = new Couchbase_QueryDefinition;
        $query->setMapFunction($map_fun);

        if($with_reduce) {
            $reduce_fun = <<<EOC_JS
            function(k,v,r) {
                return sum(v);
            }
EOC_JS;
            $query->setReduceFunction($reduce_fun);
        }

        $this->cb->addQuery("name", $query);
    }

    function test_instantiation()
    {
        $this->assertInstanceOf("Couchbase", $this->cb);
    }

    function test_basic_query()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name");
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

    function test_basic_query_with_descending_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("descending" => true));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Simon", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Ben", $result->rows[2]->key);
    }

    function test_basic_query_with_key_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("key" => "James"));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(1, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
    }


    function test_basic_query_with_startkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("startkey" => "James"));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
        $this->assertEquals("Simon", $result->rows[1]->key);
    }

    function test_basic_query_with_endkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("endkey" => "James"));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
    }

    function test_basic_query_with_startkey_and_endkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("startkey" => "James", "endkey" => "James"));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(1, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
    }

    function test_basic_query_with_limit_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("limit" => 2));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
    }

    function test_basic_query_with_limit_and_skip_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $result = $this->cb->query("name", array("limit" => 2, "skip" => 1));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
        $this->assertEquals("Simon", $result->rows[1]->key);
    }

    function test_basic_query_with_reduce()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $result = $this->cb->query("name");
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(3, $result->rows[0]->value);
    }

    function test_basic_query_with_reduce_and_reduce_option_false()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $result = $this->cb->query("name", array("reduce" => false));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

    function test_basic_query_with_reduce_and_group_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $result = $this->cb->query("name", array("group" => true));
        $this->assertInstanceOf("Couchbase_QueryResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

}
