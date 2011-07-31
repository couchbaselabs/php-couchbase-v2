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

class Couchbase_AllDocsView extends Couchbase_View
{
    /**
     * Constructor, fake ddoc and view names
     */
    function __construct()
    {
        parent::__construct("_builtin", "_all_docs");
    }

    /**
     * Return a Couchbase query result.
     * 
     * Overrides the parent's method to query `_all_docs` instead of a custom
     * view.
     *
     * @param array $options Optional associative array of view options.
     * @return Couchbase_ViewResult
     */
    function getResult($options = array())
    {
        return new Couchbase_ViewResult(
            $this->db->couchdb->allDocs($options)
        );
    }
}

/**
 * Access Couchbase views.
 *
 * @package Couchbase
 */
class Couchbase_View
{
    /**
     * Design Document id for the view.
     *
     * @todo redundant?
     * @var string Design document id.
     */
    var $_id;

    /**
     * Design Document revision for the view.
     *
     * @todo redundant?
     * @var string Design document revision.
     */
    var $_rev;

    /**
     * Database object instance.
     *
     * @var Couchbase instance to access the server.
     */
    var $db;

    /**
     * Couchbase view definition object, designed to be turned into JSON.
     *
     * @var Couchbase_ViewDefinition that holds the JavaScript function code.
     */
    var $view_definition;

    /**
     * Design doc name sans "_design/" prefix.
     *
     * @var string
     */
    var $ddoc_name;

    /**
     * View name
     *
     * @var string View name.
     */
    var $view_name;

    /**
     * Constructor, instantiates a new view with a design doc name and a view
     * name.
     *
     * @param string $ddoc_name Design doc name.
     * @param string $view_name View name.
     */
    function __construct($ddoc_name, $view_name)
    {
        $this->ddoc_name = $ddoc_name;
        $this->name = $view_name;
        $this->view_definition = new Couchbase_ViewDefinition;
    }

    /**
     * Returns a Couchbase view result.
     *
     * @param array $options Optional associative array of view options.
     * @return Couchbase_ViewResult
     */
    function getResult($options = array())
    {
        return new Couchbase_ViewResult(
            $this->db->couchdb->view($this->ddoc_name, $this->name, $options)
        );
    }

    /**
     * Returns a Couchbase view result that matches a give key.
     *
     * @param string $key Return only rows that match this key.
     * @param array $options Optional associative array of view options.
     * @return Couchbase_ViewResult
     */
    function getResultByKey($key, $options = array())
    {
        return $this->getResult(array_merge($options, array("key" => $key)));
    }

    /**
     * Returns a Couchbase view result specified by a key range.
     *
     * @param string $start First key to match in the range.
     * @param string $end First key out of range.
     * @param array $options Optional associative array of view options.
     * @return Couchbase_ViewResult
     */
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
     * @param array $options Optional associative array of view options.
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

    /**
     * Returns a result paginator for results of this view.
     *
     * @return Couchbase_ViewResultPaginator
     */
    function getResultPaginator()
    {
        return new Couchbase_ViewResultPaginator($this);
    }

    /**
     * Set a map function for this view.
     *
     * @param string $code Map function code. Must currently be JavaSCript.
     * @return void
     */
    function setMapFunction($code)
    {
        $this->view_definition->setMapFunction($code);
    }

    /**
     * Set a reduce function for this view.
     *
     * @param string $code Reduce function code. Must currently be JavaSCript.
     * @return void
     */
    function setReduceFunction($code)
    {
        $this->view_definition->setReduceFunction($code);
    }
}

