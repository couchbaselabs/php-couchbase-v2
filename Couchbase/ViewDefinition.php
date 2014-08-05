<?php
/*
 * Define a Couchbase query.
 *
 * @package Couchbase
 * @license Apache 2.0
 */

/**
 * View definition
 *
 * @property-read string $map
 * @property-read string $reduce
 * @property-read array $options
 */
class Couchbase_ViewDefinition
{
    /**
     * Source code for map function. Currently must be JavaScript.
     *
     * @var string JavaScript map function.
     */
    protected $map = "";

    /**
     * Source code for reduce function. Currently must be JavaScript.
     *
     * @var string JavaScript reduce function.
     */
    protected $reduce = null;

    /**
     * View definition options.
     *
     * @var array View definition options.
     */
    public $options = array();

    /**
     * Add map function code to the query definition.
     *
     * @param string $code map function code.
     * @return void
     */
    public function setMapFunction($code)
    {
        $this->map = $code;
    }

    /**
     * Add reduce function code to the query definition.
     *
     * @param string $code reduce function code
     * @return void
     */
    public function setReduceFunction($code)
    {
        $this->reduce = $code;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}
