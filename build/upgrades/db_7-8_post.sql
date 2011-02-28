-- $rev 1

update user set passwordversion = 1 where length(password) > 16
$$$

update subscriber set passwordversion = 1 where length(password) > 16
$$$