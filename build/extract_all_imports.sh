#!/bin/bash

#update for loop with max customerid 


for ((cust=2;cust <= 17; cust++))
do
	echo doing $cust
	time php import_extractor.php $cust
done
