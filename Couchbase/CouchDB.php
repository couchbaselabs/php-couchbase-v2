<?php
class Couchbase_CouchDB
{
    function __construct($dsn)
    {
        $this->dsn = $dsn;
        foreach(parse_url($dsn) AS $k => $v) {
            $this->server->$k = $v;
        }
    }

    function createDb($name)
    {
        return $this->send("PUT", $this->server->path);
    }

    function deleteDb($name)
    {
        return $this->send("DELETE", $this->server->path);
    }

    function saveDoc($doc)
    {
        return $this->send("POST", $this->server->path, $doc);
    }

    function open($id)
    {
        return $this->send("GET", $this->server->path . "/$id");
    }

    function view($group, $name, $options)
    {
        // TODO: keys POST
        $qs = array();
        foreach($options AS $option => $value) {
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
        $qs = join("&", $qs);
        return $this->send("GET", $this->server->path . "/_design/$group/_view/name?$qs");
    }

    function send($method, $url, $post_data = NULL)
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
            $request .= "Authorization: Basic ".base64_encode("$this->server->user:$this->server->pass")."\r\n";
        }

        if($post_data) {
            $request .= "Content-Type: application/json\r\n";
            $request .= "Content-Length: ".strlen($post_data)."\r\n\r\n";
            $request .= "$post_data\r\n";
        } else {
            $request .= "\r\n";
        }
        fwrite($s, $request); 
        $response = ""; 

        while(!feof($s)) {
            $response .= fgets($s);
        }

        list($this->headers, $this->body) = explode("\r\n\r\n", $response);

        return $this->body;
    }
}
