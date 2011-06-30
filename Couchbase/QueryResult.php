<?php
class Couchbase_QueryResult
{
    var $total_rows = 0;
    var $offset = 0;
    var $rows = array();

    function __construct($result_json) {
        $result = json_decode($result_json);
        $this->total_rows = $result->total_rows;
        $this->offset = $result->offset;
        $this->rows = $result->rows;
    }
}
