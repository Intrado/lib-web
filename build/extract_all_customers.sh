#!/bin/bash

#update for loop with max customerid 


for ((cust=11;cust <= 139; cust++))
do
	echo doing $cust
	time php extract_customer.php $cust
done