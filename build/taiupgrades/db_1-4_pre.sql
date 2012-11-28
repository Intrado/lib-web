-- $rev 1

-- insert the id found for the one and only first existing notificationtype of type 'messaging'
INSERT INTO `setting` (`name`, `value`) SELECT '_tai_notificationtypeid_newmessage', id FROM `notificationtype` WHERE `type` = 'messaging'
$$$

-- create new notification type for unread message report
INSERT INTO `notificationtype` (`name`, `systempriority`, `info`, `deleted`, `type`) VALUES ('Unread Message Report', '3', 'Unread Message Report', '0', 'messaging')
$$$

-- save the id of the unread message notification type
INSERT INTO `setting` (`name`, `value`) SELECT '_tai_notificationtypeid_unreadmessage', id FROM `notificationtype` WHERE `name` = 'Unread Message Report'
$$$


