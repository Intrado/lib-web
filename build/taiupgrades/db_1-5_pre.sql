-- $rev 1

-- copy commsuite setting for tai
INSERT INTO `setting` (name, value) SELECT 'tai_autoreport_replyemail', value from setting where name = 'autoreport_replyemail'
$$$

INSERT INTO `setting` (name, value) SELECT 'tai_autoreport_replyname', value from setting where name = 'autoreport_replyname'
$$$

-- rename
update notificationtype set name = 'Report', info = 'Report' where name = 'Unread Message Report' and type = 'messaging'
$$$

update setting set name = '_tai_notificationtypeid_report' where name = '_tai_notificationtypeid_unreadmessage'
$$$
