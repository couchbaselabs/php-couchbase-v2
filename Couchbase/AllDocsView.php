<?php
/**
 * Define a Couchbase query.
 *
 * @package Couchbase
 * @license Apache 2.0
 */

class Couchbase_AllDocsView extends Couchbase_View
{
    /**
     * Constructor, fake ddoc and view names
     */
    public function __construct(Couchbase $db)
    {
        parent::__construct("_builtin", "_all_docs", $db);
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
    public function getResult($options = array())
    {
        return new Couchbase_ViewResult(
            $this->db->couchdb->allDocs($options)
        );
    }
}