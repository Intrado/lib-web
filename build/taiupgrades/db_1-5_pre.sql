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

-- $rev 2

-- default first email and sms sequence to enabled
insert into jobtypepref select value, 'phone', 0, 1 from setting where name = '_tai_notificationtypeid_report'
$$$

insert into jobtypepref select value, 'email', 0, 1 from setting where name = '_tai_notificationtypeid_report'
$$$

insert into jobtypepref select value, 'sms', 0, 1 from setting where name = '_tai_notificationtypeid_report'
$$$

insert into jobtypepref select value, 'phone', 0, 1 from setting where name = '_tai_notificationtypeid_newmessage'
$$$

insert into jobtypepref select value, 'email', 0, 1 from setting where name = '_tai_notificationtypeid_newmessage'
$$$

insert into jobtypepref select value, 'sms', 0, 1 from setting where name = '_tai_notificationtypeid_newmessage'
$$$

-- $rev 3
-- no SQL changes, see db_1-5.php for template insertion code

-- $rev 4
-- empty, run to insert setting _dbupgrade_inprogress bug CS-4311

