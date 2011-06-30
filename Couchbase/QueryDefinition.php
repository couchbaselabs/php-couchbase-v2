<?php
class Couchbase_QueryDefinition
{
    var $map = "";

    function setMapFunction($code) {
        $this->map = $code;
    }

    function setReduceFunction($code) {
        $this->reduce = $code;
    }
}
