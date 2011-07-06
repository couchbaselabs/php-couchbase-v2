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
    var $options = array();

    function __construct($view, $rowsPerPage = 10, $pageKey = null, $options = array())
    {
        $this->view = $view;
        $this->rowsPerPage = $rowsPerPage;
        $this->page_key = $pageKey;
        $this->options = $options;
    }

    function rewind()
    {
        $this->page_key = null;
    }

    function current()
    {
        // TODO: startkey_docid/endkey_docid
        $options = array_merge($this->options,
            array("limit" => $this->rowsPerPage));
        $result = $this->view->getResultByRange($page_key, null, $options);
        return $result;
    }

    function key()
    {
        return $this->page_key;
    }

    function next()
    {
        // TODO: startkey_docid/endkey_docid
        $options = array_merge($this->options,
            array("limit" => $this->rowsPerPage + 1));
        $result = $this->view->getResultByRange(
            $this->page_key,
            null,
            $options
        );

        if($result->rows[$this->rowsPerPage]->key) {
            $row = $result->rows[$this->rowsPerPage];
            $this->page_key = array($row->key, $row->id);
        } else {
            $this->page_key = false;
        }

        unset($result->rows[$this->rowsPerPage]);
        return $result;
    }

    function valid()
    {
        $this->page_key !== false;
    }
}
