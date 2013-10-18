-- $rev 1
ALTER TABLE `alert` ADD INDEX ( `date` )
$$$
-- $rev 2
ALTER TABLE `event` ADD INDEX ( `userid` )
$$$
