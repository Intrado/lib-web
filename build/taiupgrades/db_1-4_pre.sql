-- $rev 1

-- give new permission tai_canviewunreadmessagereport to existing profiles that have tai_canviewreports
-- INSERT INTO permission (accessid, name, value) SELECT accessid, 'tai_canviewunreadmessagereport', 1 FROM permission WHERE name = 'tai_canviewreports' AND value = 1

-- insert the id found for the one and only first existing notificationtype of type 'messaging'
INSERT INTO `setting` (`name`, `value`) SELECT '_tai_notificationtypeid_newmessage', id FROM `notificationtype` WHERE `type` = 'messaging'
$$$

-- create new notification type for unread message report
INSERT INTO `notificationtype` (`name`, `systempriority`, `info`, `deleted`, `type`) VALUES ('Unread Message Report', '3', 'Unread Message Report', '0', 'messaging')
$$$

-- save the id of the unread message notification type
INSERT INTO `setting` (`name`, `value`) SELECT '_tai_notificationtypeid_unreadmessage', id FROM `notificationtype` WHERE `name` = 'Unread Message Report'
$$$


