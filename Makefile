test:
	phpunit --verbose test/CouchbaseTest.php

cover:
	phpunit --coverage-html cover test/CouchbaseTest
	open cover/index.html

doc:
	mkdir doc
	docblox -d . -t doc

clean:
	git clean -fdx


.PHONY: test cover doc
