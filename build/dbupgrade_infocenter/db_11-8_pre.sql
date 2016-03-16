-- $rev 1
alter table user add (visited tinyint default 0, jsonData text)
$$$

alter table usercustomer add (jsonData text)
$$$