test:
	phpunit --verbose test/CouchbaseTest.php

cover:
	phpunit --coverage-html cover test/CouchbaseTest
	open cover/index.html

clean:
	git clean -fdx

.PHONY: test cover
