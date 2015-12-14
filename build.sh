#!/bin/bash

dist_dir=${1:-/usr/commsuite/upgrade}
date=`date +%F`

[ -L /usr/commsuite/www ] && exit 0

ant dist || exit $?
mv dist/kona-$date.zip $dist_dir/ || exit $?
