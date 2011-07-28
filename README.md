# Couchbase PHP Client

This package allows you to access Couchbase database servers from PHP.

## Quality Notice

The code here is a first stab, none of this is final, and we may have made
a few bad choices along the way. It works in our testing, but you know how
that goes. So beware, dragons and all, and if you are so kind, let us know
of any issues you might have reading or using this code. We'd like to make
it absolutely fab for you, but we need you to tell us where we fail.

Any patches you might have to fix your pet peeves are very welcome, this is
Open Source after all.

 <https://github.com/couchbaselabs/php-couchbase>

## Quick Start

    <?php
    // setup
    require("Couchbase.php");
    $cb = new Couchbase;
    $cb->addCouchbaseServer("localhost"); // connects to Couchbase ports 11211
                                          // and 5984 by default.
    
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

#### `getValues()`

To get a list of values according to your view definition, you can use
`getValues([$options = array()])`.

Say you define three values as JSON:

    <?php
    // setup skipped
    $cb->set('a', '{"name": "Simon"}');
    $cb->set('a', '{"name": "Ben"}');
    $cb->set('a', '{"name": "James"}');

And you have a view `"by_name"` that has this map function:

    function(doc) { // note, this is JavaScript
        if(doc.name) {
          emit(doc.name);
        }
    }

Now `getValues()` will return the values you originally specified, but sorted
by the `name` field in them:

    <?php
    $values = $cb->getView("default", "by_name");
    foreach($values AS $value) {
      echo $value->name . "\n";
    }

Returns:

    Ben
    James
    Simon


Note: To use a view for this that defines a reduce function, you need to use
the `"reduce" => false` option.

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

Each row conforms to the class definition `Couchbase_ViewResultRow` below,
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

### Pagination

The Couchbase PHP SDK gives you an easy pagination API that allows you to
access your view result in pages, or spread over multiple pages.

    <?php
    // setup skipped
    $view = $cb->getView("designdoc_Name", "by_name");
    $resultPages = $view->getResultPaginator();
    foreach($resultPages AS $page) {
      // $page is a Couchbase_ViewResult instance
    }

If you are spreading the result set over actual page loads, you've got to
keep a `$page_key` around:

    <?php
    // setup skipped
    // safer version of $_GET["page_key"];
    $page_key = filter_input(INPUT_GET, "page_key", FILTER_SANITIZE_URL);

    $view = $cb->getView("designdoc_Name", "by_name");
    $resultPages = $view->getResultPaginator();
    $resultPages->setPageKey($page_key);
    $currentPage = $resultPages->current();


### Options

`getResult()` takes a second optional parameter `$options = array()` that is and
associative array of query options. Here's a list.


These options give you numeric and order control over the result set:

 - `limit`: Only return `limit` rows. Must be an integer.
 - `skip`: From the beginning or `startkey`, skip a number of rows. Must be
      an integer, use this sparingly and with small integers < 10 unless you
      know what you are doing.

 - `stale`: View results update on read. If you specify `"stale" => "ok"`,
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
   (see Couchbase_ViewResultRow), also include a `doc` member that includes
   the JSON value of the underlying data item.

## Classes

### `Couchbase_ViewResult`


    class Couchbase_ViewResult
    {

      /**
       * Number of total rows in a view result, regardless of any specified
       * parameters that might limit a view result, like `key`, `startkey`,
       * `endkey` and `limit`.
       *
       * @var int Total number of rows in a view result.
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
       * Array of result rows. This array contains all view results in an
       * array.
       *
       * @var array result rows.
       */
      var $rows = array();
    }

### `Couchbase_ViewResultRow`

Note that result rows are not actually instances of this class. This class
definition exists just for documentation purposes.

    class Couchbase_ViewResultRow
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

### `Couchbase_ViewResultPaginator`

    class Couchbase_ViewResultPaginator implements Iterator
    {
    }

### `Couchbase_ViewQueryOptions`

Note that this isn't an actual class, this just declares what query options
can be used:

    Couchbase_QueryOptions $options = array(
        "limit" => false,
        "skip" => 0,
        "descending" => false,
        "stale" => false,
        "group" => false,
        "group_level" => 0,
        "reduce" => true,
        "inclusive_end" => false // only valid in getResultByRange
    );

## Todo

 - integrate proper(!) HTTP client
 - detect new couch-api endpoints dynamically

## Copyright & License

(c) Couchbase, Inc

Apache 2.0 licensed.

https://github.com/couchbaselabs/php-couchbase

Jan Lehnardt <jan@couchbase.com>

