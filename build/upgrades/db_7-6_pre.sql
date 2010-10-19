-- $rev 1

update reportinstance set `parameters` = concat(`parameters`, '&format=csv') where `parameters` like '%reporttype=contactchangereport%' and `parameters` not like '%format=%'
$$$

