<?php
/**
 * Interface to the CouchDB HTTP API.
 *
 * @package Couchbase
 */
class Couchbase_CouchDB
{
    /**
     * CouchDB server url, parsed into an object.
     *
     * @var URL CouchDB Server URL
     */
    protected $server = null;

    /**
     * Constructor, takes a URL spcifying the CouchDB server and database.
     *
     * @param string $dsn URL to a CouchDB server and database
     * @example http://localhost:5984/database
     */
    public function __construct($dsn)
    {
        $this->server = new stdClass;
        $this->dsn = $dsn;
        foreach(parse_url($dsn) AS $k => $v) {
            $this->server->$k = $v;
        }
    }

    /**
     * Create a new database.
     *
     * @param string $name database name, must match [a-z][a-z0-9$()/_-].
     * @return string JSON success or error message.
     */
    public function createDb($name)
    {
        return $this->send("PUT", $this->server->path);
    }

    /**
     * Delete a datbase
     *
     * @param string $name database name.
     * @return string JSON success or error message.
     */
    public function deleteDb($name)
    {
        return $this->send("DELETE", $this->server->path);
    }

    /**
     * Save a document.
     *
     * @param string $doc JSON representation of a document.
     * @return string JSON success or error message.
     */
    public function saveDoc($doc)
    {
        return $this->send("POST", $this->server->path, $doc);
    }


    /**
     * Open a document by id.
     *
     * @param string $id The documents's id.
     * @return string document or error message as a JSON string.
     */
    public function open($id)
    {
        return $this->send("GET", $this->server->path . "/$id");
    }

    /**
     * Query a CouchDB view.
     *
     * @param string $group Design document / view group name.
     * @param string $name View name.
     * @param string $options Associative array of CouchDB view query options.
     * @return string JSON result set of a CouchDB view Query.
     */
    public function view($group, $name, $options = array())
    {
        $qs = $this->_options_to_query_string($options);
        return $this->send("GET",
            $this->server->path . "/_design/$group/_view/$name?$qs");
    }

    /**
     * Query the built-in _all_docs view.
     *
     * @param string $options Associative array of CouchDB view query options.
     */
    public function allDocs($options)
    {
        $qs = $this->_options_to_query_string($options);
        return $this->send("GET", $this->server->path . "/_all_docs?$qs");
    }

    /**
     * Utility function that turns an options array into CouchDB view
     * parameter query string. Including proper quoting for *key* options.
     *
     * @param string $options Associative array of CouchDBview query options.
     * @return string URL query string to be appended to a view query.
     */
    private function _options_to_query_string($options)
    {
        // TODO: keys POST
        $qs = array();
        foreach($options AS $option => $value) {

            // ignore null values that come in from optional arguments in
            // the public API like getResutlsByRange($start = null, $end = null)
            if(null === $value) {
                continue;
            }

            switch($option) {
            case "key":
            case "startkey":
            case "start_key":
            case "endkey":
            case "end_key":
                $qs[] = "$option=" . urlencode(json_encode($value));
            break;

            case "descending":
            case "group":
            case "reduce":
            case "include_docs":
            case "inclusive_end":
                $qs[] = "$option=" . ($value?"true":"false");
            break;

            default:
                $qs[] = "$option=" . urlencode($value);
            break;
            }
        }
        return join("&", $qs);
    }

    /**
     * Utility^W Gehtto method, send an HTTP request to CouchDB
     *
     * TODO: This really needs to be moved to a proper HTTP client.
     *
     * @param string $method HTTP method, GET, PUT, POST, DELETE etc.
     * @param string $url The path component of a URL.
     * @param string $post_data Data to send with a POST or PUT request.
     * @param string $content_type request contet-type header, defaults to
     *               application/json.
     * @return string JSON response.
     */
    protected function send($method, $url, $post_data = NULL, $content_type = "application/json")
    {
        $s = fsockopen(
            $this->server->host,
            $this->server->port,
            $errno,
            $errstr,
            10);
        if(!$s) {
            echo "$errno: $errstr\n";
            return false;
        }

        $host = $this->server->host;
        $request = "$method $url HTTP/1.0\r\nHost: $host\r\n";

        if(isset($this->server->user)) {
            $request .= "Authorization: Basic ".base64_encode("{$this->server->user}:{$this->server->pass}")."\r\n";
        }

        if($post_data) {
            $request .= "Content-Type: $content_type\r\n";
            $request .= "Content-Length: ".strlen($post_data)."\r\n\r\n";
            $request .= "$post_data\r\n";
        } else {
            $request .= "\r\n";
        }
        // var_dump("--------------------------------");
        // var_dump($request);
        // var_dump("-------------");
        fwrite($s, $request); 
        $response = "";

        while ( ( ( $line = fgets( $s ) ) !== false ) &&
                ( ( $lineContent = rtrim( $line ) ) === '' ) );

        while(!feof($s)) {
            $response .= fgets($s);
        }

        list($this->headers, $this->body) = explode("\r\n\r\n", $response);
        if($response == "") {
            // var_dump("                             -------------------------------");
            // var_dump("                             ERROR EMPTY SERVER RESPONSE");
            // var_dump($request);
            // var_dump("                             -------------------------------");
            // var_dump($response);
            // var_dump("                             -------------------------------");
            // exit (1);
        }
        // var_dump($response);
        // var_dump("--------------------------------");

        return $this->body;
    }
}
