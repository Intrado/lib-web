#!/bin/bash

#update for loop with max customerid 


for ((cust=$1;cust <= $2; cust++))
do
	echo doing $cust
	time php extract_customer.php $cust
done
