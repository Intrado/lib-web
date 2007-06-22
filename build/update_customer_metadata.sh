#!/bin/bash

#update for loop with max customerid 


for ((cust=1;cust <= 17; cust++))
do
	echo doing $cust
	time php update_metadata.php $cust
done
