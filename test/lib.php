<?php
/**
 * Couchbase test utility library.
 *
 * @package Couchbase
 */

class Couchbase_Test_Lib
{
    function __construct($couchbase)
    {
        $this->cb = $couchbase;
    }

    function prepare_docs()
    {
        $ids = array(
            "123",
            "456",
            "789"
        );

        $doc = new stdClass;
        $doc->name = "Simon";
        $json_doc = json_encode($doc);
        $this->cb->set($ids[0], $json_doc);

        $doc = new stdClass;
        $doc->name = "Ben";
        $json_doc = json_encode($doc);
        $this->cb->set($ids[1], $json_doc);

        $doc = new stdClass;
        $doc->name = "James";
        $json_doc = json_encode($doc);
        $this->cb->set($ids[2], $json_doc);

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

        $view = new Couchbase_View("default", "name");
        $view->setMapFunction($map_fun);

        if($with_reduce) {
            $reduce_fun = <<<EOC_JS
            function(k,v,r) {
                return sum(v);
            }
EOC_JS;
            $view->setReduceFunction($reduce_fun);
        }

        $this->cb->addView($view);
    }
}
