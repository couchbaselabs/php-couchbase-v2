<?php

/**
 * A Couchbase query result.
 *
 * Objects of this class represent a Couchbase query result.
 *
 * @package Couchbase
 * @license Apache 2.0
 * @property-read int $total_rows
 * @property-read int $offset
 * @property-read array $rows
 * @property-read array $errors
 */
class Couchbase_ViewResult
{

    /**
     * Number of total rows in a query result, regardless of any specified
     * parameters that might limit a view result, like `key`, `startkey`,
     * `endkey` and `limit`.
     *
     * @var int Total number of rows in a query result.
     */
    protected $total_rows = 0;

    /**
     * Number indicating how many rows are skipped from the beginning of the
     * total result set. Counts from the end if the descending option is set
     * to true.
     *
     * @var int Number of rows skipped.
     */
    protected $offset = 0;

    /**
     * Array of result rows. This array contains all query results in an
     * array.
     *
     * @var array result rows.
     */
    public $rows = array();

    /**
     * Array of error rows. This array contains all errors from the query.
     * Or only one, if the on_error=stop option is used.
     * It is `null` when there are no errors.
     */
    protected $errors = null;

    /**
     * Constrictor, takes a CouchDB view result JSON string as a parameter.
     *
     * @param string $result_json CouchDB view result.
     */
    public function __construct($result_json)
    {
        $result = json_decode($result_json);

        if (isset($result->total_rows)) {
            $this->total_rows = $result->total_rows;
        }

        if (isset($result->offset)) {
            $this->offset = $result->offset;
        }

        if (isset($result->rows)) {
            $this->rows = $result->rows;
        }

        if (isset($result->errors)) {
            $this->errors = $result->errors;
        }
    }

    public function __get($name)
    {
        return $this->$name;
    }

}
