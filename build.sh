#!/bin/bash
# build.sh - every project has a thin bash script that can run a build,
# and produce a zip archive to be deployed.
# The first argument is the directory into which to deliver the zip.

dist_dir=${1:-/tmp/upgrade}
date=${2:-`date +%F_%H_%M_%S`}

mkdir -p $dist_dir

if [ -L /usr/commsuite/www ]
then
	exit 0
fi

ant dist
if [ $? -ne 0 ]; then
       echo "ERROR: Build Failed!"
       exit 1
fi

mv dist/kona*.zip $dist_dir/kona-$date.zip
if [ $? -ne 0 ]; then
       echo "ERROR: Moving the ZIP failed!"
       exit 1
fi
