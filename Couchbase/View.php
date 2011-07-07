<?php
/**
 * Define a Couchbase query.
 *
 * @package Couchbase
 * @license Apache 2.0
 */

/*
TODO: Add query options and different languages.
*/
class Couchbase_View
{
    var $_id;
    var $_rev;
    var $db;
    var $view_definition;

    function __construct()
    {
        $this->view_definition = new Couchbase_ViewDefinition;
    }

    function getResult($options = array())
    {
        return new Couchbase_ViewResult(
            $this->db->couchdb->view($this->ddoc_name, $this->name, $options)
        );
    }

    function getResultByKey($key, $options = array())
    {
        return $this->getResult(array_merge($options, array("key" => $key)));
    }

    function getResultByRange($start, $end = null, $options = array())
    {
        $key_options = $startkey_options = $endkey_options = array();

        if(is_array($start)) {
            // TODO: throw warning if either is empty
            $startkey_options = array("startkey" => $start[0], "startkey_docid" => $start[1]);
        } else {
            $startkey_options = array("startkey" => $start);
        }

        if(is_array($end)) {
            // TODO: throw warning if either is empty
            $endkey_options = array("endkey" => $end[0], "endkey_docid" => $end[1]);
        } else {
            $endkey_options = array("endkey" => $end);
        }

        $key_options = array_merge($startkey_options, $endkey_options);
        return $this->getResult(array_merge($options, $key_options));
    }

    /**
     * Retrieve values from cache in view result order.
     *
     * @return void
     */
    function getValues($options = array())
    {
        $result = $this->getResult($options);

        // TODO: make this a anonymous function when 5.3 is our minimum version
        function extract_id($row) { return $row->id; }
        $ids = array_map("extract_id", $result->rows);

        $multi_result = $this->db->getMulti($ids);
        // TODO: make this a anonymous function when 5.3 is our minimum version
        function jsonize($s) { return json_decode($s); }
        $jsoned_result = array_map("jsonize", $multi_result);
        return($jsoned_result);
    }

    function getResultPaginator()
    {
        return new Couchbase_ViewResultPaginator($this);
    }

    function setMapFunction($code)
    {
        $this->view_definition->setMapFunction($code);
    }

    function setReduceFunction($code)
    {
        $this->view_definition->setReduceFunction($code);
    }
}

