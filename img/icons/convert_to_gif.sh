#!/bin/bash


for x in `ls *.png`
do
	in=$x
	out="${x%%png}gif"
	echo $in
	convert $in $out
done

