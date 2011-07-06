# Couchbase PHP Client

This package allows you to access Couchbase database servers from PHP.

## Quick Start

    <?php
    // setup
    require("Couchbase.php");
    $cb = new Couchbase;
    $cb->addServer("localhost", 11211);
    
    // start storing data
    $cb->set("my_key", "my_data");
    $result = $cb->get("my_key");
    var_dump($result);

    // Done!

## Introduction

The Couchbase PHP client extends the existing php.net/memcached client
interface and adds Couchbase specific features.

All your code using php.net/memcached will work without changes (hooray).

To use the new features, you will have to use the Couchbase database version
2.0 or later. Please note that the Couchbase Single Server 2.0 is not
compatible with this (TBD see also).


## Additions to php.net/memcached

### `touch()` Method

Couchbase adds a method `touch($key[, $expiry = 0])` that allows you to reset
the expiration of a key. Previous ways of doing the same thing included
sending the key's value to the server again, `touch()` obviates that.

### Views

Couchbase 2.0 introduces a way to query your data with _Views_.

The Couchbase admin interface allows you to define and name your views.

Note, There is a way to programatically create new views that we'll explain
later.

#### `getResult()`

Once you created your queries in the admin interface, you can reference them
by name in your code. Say you create a new view `by_name`:

    <?php
    // setup skipped
    $view = $cb->getView("designdoc_name", "by_name");

`getView()` returns a `Couchbase_View` (see below) instance that you can
 use to run a query on that view:

    <?php
    // setup skipped
    $view = $cb->getView("designdoc_name", "by_name");
    $result = $view->getResult();
    foreach($result->rows AS $row) {
      echo $row->value;
    }

Each row conforms to the class definition `Couchbase_QueryResultRow` below,
but note that the result row objects are not actually instances of that class,
they are just described by it.

#### `getResultByKey()`

`getResultKey($key[, $options = array()])` allows you to return all the rows
of a view result that match `$key` exactly.

    <?php
    // setup skipped
    $view = $cb->getView("designdoc_name", "by_name");
    $result = $view->getResultByKey("Foo Fighters");
    foreach($result->rows AS $row) {
      echo $row->value;
    }

#### `getResultByRange()`

`getResultRange([$start = null][, $end = null][, $options])` allows you to
retrieve a range from the view result delimited by the `$start` and `$end`
arguments, both of which are optional.

    <?php
    // setup skipped
    $view = $cb->getView("designdoc_name", "by_name");
    $result = $view->getResultByRange("A", "M");
    foreach($result->rows AS $row) {
      echo $row->value;
    }


### Options

`getResult()` takes a second optional parameter `$options = array()` that is and
associative array of query options. Here's a list.


These options give you numeric and order control over the result set:

 - `limit`: Only return `limit` rows. Must be an integer.
 - `skip`: From the beginning or `startkey`, skip a number of rows. Must be
      an integer, use this sparingly and with small integers < 10 unless you
      know what you are doing.

 - `stale`: Query results update on read. If you specify `"stale" => "ok"`,
      you will get an immediate reply, but you may get out of date data.
      If you specify `"stale" => "update_after"`, the query returns
      immediately and an index update is triggered *after* the result is
      returned.

 - `descending`: Specify whether you want to walk the result set backwards.
      Must be a boolean. Defaults to `false`.

These options are for advanced queries that use a `reduce`. (TBD)

 - `group`
 - `group_level`
 - `reduce`

This last one doesn't fit in any other category:

 - `include_docs`: along with the `key` and `value`
   (see Couchbase_QueryResultRow), also include a `doc` member that includes
   the JSON value of the underlying data item.

## Classes

### `Couchbase_QueryResult`


    class Couchbase_QueryResult
    {

      /**
       * Number of total rows in a query result, regardless of any specified
       * parameters that might limit a view result, like `key`, `startkey`,
       * `endkey` and `limit`.
       *
       * @var int Total number of rows in a query result.
       */
      var $total_rows = 0;

      /**
       * Number indicating how many rows are skipped from the beginning of the
       * total result set. Counts from the end if the descending option is set
       * to true.
       *
       * @var int Number of rows skipped.
       */
      var $offset = 0;

      /**
       * Array of result rows. This array contains all query results in an
       * array.
       *
       * @var array result rows.
       */
      var $rows = array();
    }

### `Couchbase_QueryResultRow`

Note that result rows are not actually instances of this class. This class
definition exists just for documentation purposes.

    class Couchbase_QueryResultRow
    {
        /**
         * A query result is ordered by `key`, in each row, you can access
         * the key with `$row->key`.
         */
        var $key;

        /**
         * Each row in a query result can hold an arbitrary `value` that can
         * be accessed with `$row->value`.
         */
        var $value;

        /**
         * If the `"include_docs" => true` option is specified, include the
         * full JSON representation of the underlying data item.
         */
        var $doc;
    }
