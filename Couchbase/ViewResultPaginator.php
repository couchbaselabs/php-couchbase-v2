<?php
/**
 * Define a Couchbase query.
 *
 * @package Couchbase
 * @license Apache 2.0
 */

class Couchbase_ViewResultPaginator implements Iterator
{
    var $view;
    var $rowsPerPage;
    var $page_key = null;
    var $next_page_key= null;
    var $options = array();

    function __construct($view)
    {
        $this->view = $view;
    }

    function setRowsPerPage($rowsPerPage) {
        $this->rowsPerPage = $rowsPerPage;
    }

    function setPageKey($pageKey) {
        $this->page_key = $pageKey;
    }

    function setOptions($options) {
        $this->options = $options;
    }

    function rewind()
    {
        $this->page_key = null;
        $this->current();
    }

    function current()
    {
        $options = array_merge($this->options,
            array("limit" => $this->rowsPerPage + 1));
        $result = $this->view->getResultByRange(
            $this->page_key,
            null,
            $options
        );
        // TODO: descending, flip start/end

        // if there is an extra row at the end, grab it's key and docid and
        // store them as the next_page_key
        if(isset($result->rows[$this->rowsPerPage]) && $result->rows[$this->rowsPerPage]->key) {
            $row = $result->rows[$this->rowsPerPage];
            $this->next_page_key = array($row->key, $row->id);
        } else {
            $this->next_page_key = false;
        }

        unset($result->rows[$this->rowsPerPage]);
        return $result;
    }

    function key()
    {
        return $this->page_key;
    }

    function next()
    {
        $this->page_key = $this->next_page_key;
        $this->next_page_key = null;
    }

    function valid()
    {
        $this->page_key !== false;
    }
}
