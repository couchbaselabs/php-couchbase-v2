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

        // Couchbase_QueryOptions $options = array(
        //     "limit" => false,
        //     "skip" => 0,
        //     "descending" => false,
        //     "stale" => false,
        //     "group" => false,
        //     "group_level" => 0,
        //     "reduce" => true,
        //     "inclusive_end" => false // only valid in getResultsByRange
        // );

        // key options
        // $start = "key";
        // $start = array("key", "docid");
        // $end = "key";
        // $end = array("key", "docid");

        // $result = $view->getResults([$options]);
        // $result = $view->getResultsByKey($key, [$options]);
        // $result = $view->getResultsRange($start, $end, [$options]);
        // $result = $query->getResultPage([$pagekey = null]);
        // 
        // class Couchbase_ViewResultPage extends Couchbase_ViewResult {
        //     var $page_id;
        //     var $next_page_id;
        //     var $rows
        // }

        $result = $view->getResults();
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
        $result = $view->getResults(array("descending" => true));
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

        $result = $view->getResultsByKey("James");
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(1, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
    }

    function test_basic_query_with_startkey_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResultsByRange("James");
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
        $result = $view->getResultsByRange(null, "James");
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
        $result = $view->getResultsByRange("James", "James");
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(1, count($result->rows));
        $this->assertEquals("James", $result->rows[0]->key);
    }

    function test_basic_query_with_limit_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $result = $view->getResults(array("limit" => 2));
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
        $result = $view->getResults(array("limit" => 2, "skip" => 1));
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
        $result = $view->getResults();
        $this->assertInstanceOf("Couchbase_ViewResult", $result);
        $this->assertEquals(3, $result->rows[0]->value);
    }

    function test_basic_query_with_reduce_and_reduce_option_false()
    {
        $this->prepare_docs();
        $this->prepare_ddoc($with_reduce = true);

        $view = $this->cb->getView("default", "name");
        $result = $view->getResults(array("reduce" => false));
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
        $result = $view->getResults(array("group" => true));
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
        $rowsPerPage = 2;
        $resultPages = $view->getResultsPaginator($rowsPerPage);
        foreach($resultPages AS $resultPage) {
            $this->assertInstanceOf("Couchbase_ViewResult", $resultPage);
        }

        $resultPages = $view->getResultsPaginator($rowsPerPage);
        $firstPage = $resultPages->next();
        $this->assertEquals(2, count($firstPage->rows));
        $this->assertEquals("Ben", $firstPage->rows[0]->key);
        $this->assertEquals("James", $firstPage->rows[1]->key);

        $secondPage = $resultPages->next();
        $this->assertEquals(1, count($secondPage->rows));
        $this->assertEquals("Simon", $secondPage->rows[0]->key);
    }

    function test_result_page_explicit_pages()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $rowsPerPage = 2;
        $resultPages = $view->getResultsPaginator($rowsPerPage);
        $resultPages->next();
        $pageKey = $resultPages->key();

        $resultPages = $view->getResultsPaginator($rowsPerPage, $pageKey);
        $secondPage = $resultPages->next();
        $this->assertEquals(1, count($secondPage->rows));
        $this->assertEquals("Simon", $secondPage->rows[0]->key);
    }

    function test_result_page_with_option()
    {
        $this->prepare_docs();
        $this->prepare_ddoc();

        $view = $this->cb->getView("default", "name");
        $rowsPerPage = 2;

        $resultPages = $view->getResultsPaginator($rowsPerPage, null, array("descending" => true));
        $firstPage = $resultPages->next();
        $this->assertEquals(2, count($firstPage->rows));
        $this->assertEquals("Simon", $firstPage->rows[0]->key);
        $this->assertEquals("James", $firstPage->rows[1]->key);

        $secondPage = $resultPages->next();
        $this->assertEquals(1, count($secondPage->rows));
        $this->assertEquals("Ben", $secondPage->rows[0]->key);
    }

}
