#!/bin/bash

echo "TEST: phplib"

# Lint check (syntax) all the source files
for f in `find src -name "*.php"`
do
	php -l $f
	if [ $? -ne 0 ]
	then
		echo "FAIL! :-("
		exit 1
	fi
done

echo "Done! :-)"

