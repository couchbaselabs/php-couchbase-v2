<?php
/**
 * Iterator class that allow paginating over a Couchbase_ViewResult.
 *
 * @package Couchbase
 * @license Apache 2.0
 */

class Couchbase_ViewResultPaginator implements Iterator
{
    /**
     * Couchbase_View instance to paginate over. This is our data source.
     *
     * @var Couchbase_View Data source
     */
    var $view;

    /**
     * Rows per page.
     *
     * @var integer Rows per page.
     */
    var $rowsPerPage;

    /**
     * Page key. Each page is associated with a page key that in turn is
     * composed of the first result row's key and docid. The first page's
     * key is `null`.
     *
     * @var array Row key and docid `array($key, $docid)`.
     */
    var $page_key = null;

    /**
     * The next page's key, needed for iteration.
     *
     * @var array Row key and docid `array($key, $docid)`.
     */
    var $next_page_key= null;

    /**
     * View query options.
     *
     * @var array View query options.
     */
    var $options = array();

    /**
     * Constructor.
     *
     * @param Couchbase_View $view Data source.
     */
    function __construct($view)
    {
        $this->view = $view;
    }

    /**
     * Set how many rows per page should be returned.
     *
     * @param integer $rowsPerPage 
     * @return void
     */
    function setRowsPerPage($rowsPerPage) {
        $this->rowsPerPage = $rowsPerPage;
    }

    /**
     * Set the page key
     *
     * @param array $pageKey Page key.
     * @return void
     */
    function setPageKey($pageKey) {
        $this->page_key = $pageKey;
    }

    /**
     * Specify view query options
     *
     * @param array $options View query options.
     * @return void
     */
    function setOptions($options) {
        $this->options = $options;
    }

    /**
     * PHP Iterator interface: Rewind the iterator.
     *
     * @return void
     */
    function rewind()
    {
        $this->page_key = null;
        $this->current();
    }

    /**
     * PHP Iterator interface: return the current page
     *
     * @return Couchbase_ViewResult Result page.
     */
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

    /**
     * PHP Iterator interface: return the current page's key.
     *
     * @return array Page key.
     */
    function key()
    {
        return $this->page_key;
    }

    /**
     * PHP Iterator interface: advance to the next page.
     *
     * @return void
     */
    function next()
    {
        $this->page_key = $this->next_page_key;
        $this->next_page_key = null;
    }

    /**
     * PHP Iterator interface: Do we have a valid page key (are we on the
     * last/first page).
     *
     * @return void
     */
    function valid()
    {
        return $this->page_key !== false;
    }
}
