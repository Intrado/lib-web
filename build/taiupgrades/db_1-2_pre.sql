-- $rev 1
alter table tai_topicuser add notify tinyint(4) not null default 1
$$$

-- $rev 2
INSERT INTO tai_topic (`name`) VALUES ('SMS Messages')
$$$

INSERT INTO setting (`name`,`value`) VALUES ('smsinboundtopicname','SMS Messages');
$$$

INSERT INTO `notificationtype` (`name`, `systempriority`, `info`, `deleted`, `type`) VALUES ('Topic Notifications', '3', 'Topic Notifications', '0', 'messaging');
$$$
