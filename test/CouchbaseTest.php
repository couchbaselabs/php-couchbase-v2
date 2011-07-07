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
    function setUp()
    {
        $this->cb = new Couchbase;
        $this->cb->addCouchbaseServer("localhost", 11211, 5984);
        $this->cb->couchdb->deleteDb("default");
        $this->cb->couchdb->createDb("default");
    }

    function tearDown()
    {
        unset($this->cb);
    }

    function prepare_docs($and_membase_values = false)
    {
        $ids = array();

        $doc = new stdClass;
        $doc->name = "Simon";
        $json_doc = json_encode($doc);
        $res = $this->cb->couchdb->saveDoc($json_doc);
        $id = json_decode($res)->id;
        if($and_membase_values) {
            $this->cb->set($id, $json_doc);
        }
        $ids[] = $id;

        $doc = new stdClass;
        $doc->name = "Ben";
        $json_doc = json_encode($doc);
        $res = $this->cb->couchdb->saveDoc($json_doc);
        $id = json_decode($res)->id;
        if($and_membase_values) {
            $this->cb->set($id, $json_doc);
        }
        $ids[] = $id;

        $doc = new stdClass;
        $doc->name = "James";
        $json_doc = json_encode($doc);
        $res = $this->cb->couchdb->saveDoc($json_doc);
        $id = json_decode($res)->id;
        if($and_membase_values) {
            $this->cb->set($id, $json_doc);
        }
        $ids[] = $id;

        return $ids;
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

        $view = new Couchbase_View;
        $view->setMapFunction($map_fun);

        if($with_reduce) {
            $reduce_fun = <<<EOC_JS
            function(k,v,r) {
                return sum(v);
            }
EOC_JS;
            $view->setReduceFunction($reduce_fun);
        }

        $this->cb->addView("default", "name", $view);
    }

    function test_instantiation()
    {
        $this->assertInstanceOf("Couchbase", $this->cb);
    }

    function test_basic_query()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $this->assertInstanceOf("Couchbase_View", $view);

        $result = $view->getResult();
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

    function test_basic_query_with_descending_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResult(array("descending" => true));
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Simon", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Ben", $result->rows[2]->key);
    }

    function test_basic_query_with_key_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();
        $view = $this->cb->getView("default", "name");

        $result = $view->getResultByKey("James");
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(1, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
    }

    function test_basic_query_with_startkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResultByRange("James");
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
        $this->assertEquals("Simon", $result->rows[1]->key);
    }

    function test_basic_query_with_startkey_and_docid_option()
    {
        $docids = $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResultByRange(array("James", $docids[1]));
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
        $this->assertEquals("Simon", $result->rows[1]->key);
    }

    function test_basic_query_with_endkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResultByRange(null, "James");
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
    }

    function test_basic_query_with_startkey_and_endkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResultByRange("James", "James");
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(1, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
    }

    function test_basic_query_with_limit_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResult(array("limit" => 2));
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
    }

    function test_basic_query_with_limit_and_skip_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResult(array("limit" => 2, "skip" => 1));
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(2, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
        $this->assertEquals("Simon", $result->rows[1]->key);
    }

    function test_basic_query_with_reduce()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $view = $this->cb->getView("default", "name");
        $result = $view->getResult();
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(3, $result->rows[0]->value);
    }

    function test_basic_query_with_reduce_and_reduce_option_false()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $view = $this->cb->getView("default", "name");
        $result = $view->getResult(array("reduce" => false));
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

    function test_basic_query_with_reduce_and_group_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $view = $this->cb->getView("default", "name");
        $result = $view->getResult(array("group" => true));
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(3, count($result->rows));
        $this->assertEquals("Ben", $result->rows[0]->key);
        $this->assertEquals("James", $result->rows[1]->key);
        $this->assertEquals("Simon", $result->rows[2]->key);
    }

    function test_result_page()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $resultPages = $view->getResultPaginator();
        $resultPages->setRowsPerPage(2);
        $this->assertInstanceOf("Couchbase_ViewResultPaginator", $resultPages);
        foreach($resultPages AS $resultPage) {
            $this->assertInstanceOf("Couchbase_ViewResult", $resultPage);
        }

        $resultPages = $view->getResultPaginator();
        $resultPages->setRowsPerPage(2);
        $firstPage = $resultPages->current();
        $this->assertEquals(2, count($firstPage->rows));
        $this->assertEquals("Ben", $firstPage->rows[0]->key);
        $this->assertEquals("James", $firstPage->rows[1]->key);

        $resultPages->next(); // loooop
        $secondPage = $resultPages->current();
        $this->assertEquals(1, count($secondPage->rows));
        $this->assertEquals("Simon", $secondPage->rows[0]->key);
    }

    function test_result_page_explicit_pages()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $resultPages = $view->getResultPaginator();
        $resultPages->setRowsPerPage(2);
        $resultPages->rewind();
        $resultPages->next();
        $pageKey = $resultPages->key();

        $resultPages = $view->getResultPaginator();
        $resultPages->setRowsPerPage(2);
        $resultPages->setPageKey($pageKey);

        // $resultPages->next(); // loooop
        $secondPage = $resultPages->current();
        $this->assertEquals(1, count($secondPage->rows));
        $this->assertEquals("Simon", $secondPage->rows[0]->key);
    }

    function test_result_page_with_descending_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $resultPages = $view->getResultPaginator();
        $resultPages->setRowsPerPage(2);
        $resultPages->setOptions(array("descending" => true));

        $resultPages->next(); // loooop
        $firstPage = $resultPages->current();
        $this->assertEquals(2, count($firstPage->rows));
        $this->assertEquals("Simon", $firstPage->rows[0]->key);
        $this->assertEquals("James", $firstPage->rows[1]->key);

        $resultPages->next(); // loooop
        $secondPage = $resultPages->current();
        $this->assertEquals(1, count($secondPage->rows));
        $this->assertEquals("Ben", $secondPage->rows[0]->key);
    }

    // getValues()

    function test_value_return_membase_style()
    {
        $ids = $this->prepare_docs($and_membase_values = true);
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $values = $view->getValues();
        $this->assertEquals("Ben", $values[$ids[1]]->name);
        $this->assertEquals("James", $values[$ids[2]]->name);
        $this->assertEquals("Simon", $values[$ids[0]]->name);
    }

    // error handling
    // function test_basic_query_with_error()
    // {
    //     $this->prepare_docs();
    //     $this->prepare_ddoc();
    // 
    //     $view = $this->cb->getView("default", "name_with_timeout_error");
    //     $this->assertInstanceOf("Couchbase_View", $view);
    // 
    //     $result = $view->getResult();
    //     $this->assertInstanceOf("Couchbase_ViewResult", $result);
    //     $this->assertTrue($result->error());
    //     $this->assertEquals("timeout", $result->errorMessage());
    // }
}
