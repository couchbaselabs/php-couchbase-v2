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
class Couchbase_QueryDefinition
{
    /**
     * Source code for map function. Currently must be JavaScript.
     *
     * @var string JavaScript map function.
     */
    var $map = "";

    /**
     * Source code for reduce function. Currently must be JavaScript.
     *
     * @var string JavaScript reduce function.
     */
    var $reduce = "";

    /**
     * Add map function code to the query definition.
     *
     * @param string $code map function code.
     * @return void
     */
    function setMapFunction($code)
    {
        $this->map = $code;
    }

    /**
     * Add reduce function code to the query definition.
     *
     * @param string $code reduce function code
     * @return void
     */
    function setReduceFunction($code)
    {
        $this->reduce = $code;
    }
}
