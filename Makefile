test:
	phpunit --verbose test/CouchbaseTest.php

clustertest:
	./test/cluster-setup.sh
	phpunit --verbose test/CouchbaseClusterTest.php

cover:
	phpunit --coverage-html cover test/CouchbaseTest
	open cover/index.html

doc:
	mkdir -p doc
	docblox -d . -t doc

clean:
	git clean -fdx

.PHONY: test cover doc
