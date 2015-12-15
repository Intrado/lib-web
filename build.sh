#!/bin/bash

dist_dir=${1:-/tmp/upgrade}
date=`date +%F`

mkdir -p $dist_dir

[ -L /usr/commsuite/www ] && exit 0

ant dist || exit $?
mv dist/kona-$date.zip $dist_dir/ || exit $?
