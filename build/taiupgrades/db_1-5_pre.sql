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


-- $rev 5
CREATE TABLE tai_messageattachment (
	id int(11) NOT NULL AUTO_INCREMENT,
	messageid int(11) NOT NULL,
	contentid bigint(20) NOT NULL,
	filename varchar(255) NOT NULL,
	`size` int(11) NOT NULL,
	PRIMARY KEY (id),
	KEY messageid (messageid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$

-- $rev 6
ALTER TABLE tai_message ADD `type` ENUM( 'text', 'html' ) NOT NULL DEFAULT 'html' AFTER method
$$$
