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
                                          // and 8091 by default.
    
    // start storing data
    $cb->set("my_key", "my_data");
    $result = $cb->get("my_key");
    var_dump($result);
    
    // Done!

## Introduction

The Couchbase PHP client extends the existing php.net/memcached client
interface and adds Couchbase specific features.

All your code using php.net/memcached will work without changes (hooray).

This client is for Couchbase and Membase releases of the 1.7.x line.


## Additions to php.net/memcached

### `touch()` Method

Couchbase adds a method `touch($key[, $expiry = 0])` that allows you to reset
the expiration of a key. Previous ways of doing the same thing included
sending the key's value to the server again, `touch()` obviates that.

## Todo

 - local-cache server design docs
 - make E_ALL | E_STRICT | E_DEPRECATED compatible
 - add proper credits

## Credits

php-memcached extension.
libmemcached library.


## Copyright & License

(c) 2011 Couchbase, Inc

Apache 2.0 licensed.

https://github.com/couchbaselabs/php-couchbase

Jan Lehnardt <jan@couchbase.com>
