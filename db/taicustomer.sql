-- This file contains product specific schema for TAI

INSERT INTO `setting` (`name`, `value`) VALUES ('_dbtaiversion', '0.1/1')
$$$

-- Topic is similar to the subject in an email. The topic table contains a list of preapproved topics that the sender has to pick from.
CREATE TABLE `tai_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
CREATE TABLE `tai_topicuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `isbcc` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$ 

CREATE TABLE `tai_organizationtopic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `topicid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Thread is a conversation between a sender and a recipient, with CCs based on topic. New threads can be forked from another thread, however the originating user of the first thread should be maintained in the originatinguserid field, along with wassentanonymously.
CREATE TABLE `tai_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `originatinguserid` int(11) NOT NULL,
  `recipientuserid` int(11) NOT NULL,
  `topicid` int(11) NOT NULL,
  `parentthreadid` int(11) DEFAULT NULL,
  `wassentanonymously` tinyint(4) NOT NULL DEFAULT '0',
  `modifiedtimestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Folders allow users to organize threads. Some folders will be created by the system, such as inbox (or unsorted), and trash. Trash is a first level delete; threads are moved to the trash before being deleted.
CREATE TABLE `tai_folder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('sent','trash','custom') NOT NULL DEFAULT 'custom',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Userthread associate a thread to the involved users, both the sender and the recipient, and any CC based on topic. When a user deletes a thread (moves to trash, then deletes from there), the isdelete flag is set for this userthread, and all existing (but not future) usermessages.
CREATE TABLE `tai_userthread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `threadid` int(11) NOT NULL,
  `folderid` int(11) DEFAULT NULL,
  `isdeleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Message always belong to a conversation. A message can not be a draft. Messages have a sender, and recipient. This is analogous to an email FROM and single TO, with CCs happening implicitly via the thread's topic.
CREATE TABLE `tai_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `threadid` int(11) NOT NULL,
  `senderuserid` int(11) NOT NULL,
  `recipientuserid` int(11) NOT NULL,
  `method` enum('web','sms') NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `body` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
 
-- Usermessage keeps track of user+message specific data, such as if that user has read this message, or deleted this message component of the thread.
CREATE TABLE `tai_usermessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `isread` tinyint(4) NOT NULL DEFAULT '0',
  `isdeleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
 
CREATE TABLE `tai_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `publishtimestamp` int(11) NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `body` text NOT NULL,
  `deleted` TINYINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$

-- $rev 2

CREATE TABLE `tai_cannedresponse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `body` text NOT NULL,
  `enabled` TINYINT NOT NULL DEFAULT '1',
  `deleted` TINYINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
$$$

-- $rev 3
CREATE TABLE `tai_survey` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
$$$

-- $rev 4
CREATE TABLE `tai_lockout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `title` text NOT NULL,
  `type` enum('hard','soft') NOT NULL DEFAULT 'hard',
  `starttimestamp` time NOT NULL,
  `endtimestamp` time NOT NULL,
  `days` varchar(255) NOT NULL,
  `messagebody` text NOT NULL,
  `deleted` TINYINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
$$$


-- $rev 5
alter table tai_thread modify topicid int(11)
$$$
alter table tai_thread add threadtype enum('thread', 'comment', 'identityreveal') not null default 'thread'
$$$            

-- $rev 6
alter table tai_userthread add column frombcc tinyint(4) not null default 0;
$$$

-- $rev 7
alter table tai_lockout add start_date DATE;
$$$
alter table tai_lockout add end_date DATE;
$$$

-- $rev 8
alter table tai_thread add identityrequested tinyint(4) not null default 0
$$$

update setting set value='0.1/8' where name='_dbtaiversion'
$$$

-- $rev 9
ALTER TABLE tai_userthread ADD INDEX `userfolder` ( `userid` , `folderid` ) 
$$$
ALTER TABLE tai_usermessage ADD INDEX `usermessage` ( `userid` , `messageid` )
$$$

update setting set value='0.1/9' where name='_dbtaiversion'
$$$
-- END 0.1/9

-- $rev 10
alter table tai_organizationtopic add enabled tinyint(4) not null default 1
$$$
ALTER TABLE tai_folder CHANGE `type` `type` ENUM( 'inbox', 'sent', 'trash', 'custom' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'custom'
$$$

update setting set value='0.1/10' where name='_dbtaiversion'
$$$
-- END 0.1/10

-- $rev 11
ALTER TABLE `tai_organizationtopic` DROP `enabled`;
$$$
ALTER TABLE `tai_userthread` DROP `frombcc` ;
$$$

update setting set value='0.1/11' where name='_dbtaiversion'
$$$
-- END 0.1/11

-- Default Topics 
INSERT INTO tai_topic (`name`) VALUES 
('Abuse'),('Alcohol'),('Body Image'),('Bullying'),('Cheating in School'),('Cutting/Self-Injury'),('Cyber Bullying'),('Dating Violence'),('Depression'),('Discrimination'),('Drugs'),('Eating Disorder'),('Family Problems'),('Fighting'),('Hazing'),('Health Problems'),('Peer Pressure'),('Pregnancy'),('Schoolwork Concerns'),('Sexual Harassment'),('Smoking'),('Stealing'),('Stress'),('Student/Teacher Relationship'),('Suicide'),('Threats'),('Vandalism'),('Violence'),('Weapons')
$$$

-- NOTE Version unchanged (0.1/11). Default topics only inserted in taicustomer and not in tai upgrade

-- START 1.2

-- $rev 1
alter table tai_topicuser add notify tinyint(4) not null default 1
$$$

update setting set value='1.2/1' where name='_dbtaiversion'
$$$

-- END 1.2/1

-- $rev 2
INSERT INTO tai_topic (`name`) VALUES ('SMS Messages')
$$$

INSERT INTO setting (`name`,`value`) VALUES ('smsinboundtopicname','SMS Messages');
$$$

INSERT INTO `notificationtype` (`name`, `systempriority`, `info`, `deleted`, `type`) VALUES ('Topic Notifications', '3', 'Topic Notifications', '0', 'messaging');
$$$

update setting set value='1.2/2' where name='_dbtaiversion'
$$$
-- END 1.2/2

-- START 1.3

-- $rev 1

update setting set value='1.3/1' where name='_dbtaiversion'
$$$

-- END 1.3/1

-- START 1.4

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

update setting set value='1.4/1' where name='_dbtaiversion'
$$$

-- END 1.4/1

-- START 1.5/1

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

update setting set value='1.5/1' where name='_dbtaiversion'
$$$

-- END 1.5/1

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

update setting set value='1.5/2' where name='_dbtaiversion'
$$$

-- END 1.5/2

-- no SQL changes, see db_1-5.php for template insertion code

update setting set value='1.5/3' where name='_dbtaiversion'
$$$

-- END 1.5/3

-- bug CS-4311
insert into setting (name, value) values ('_dbtaiupgrade_inprogress', 'none')
$$$

update setting set value='1.5/4' where name='_dbtaiversion'
$$$

-- END 1.5/4

-- BEGIN 1.5/5
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
update setting set value='1.5/5' where name='_dbtaiversion'
$$$
-- END 1.5/5

-- BEGIN 1.5/6
ALTER TABLE tai_message ADD `type` ENUM( 'text', 'html' ) NOT NULL DEFAULT 'html' AFTER method
$$$
update setting set value='1.5/6' where name='_dbtaiversion'
$$$
-- END 1.5/6
