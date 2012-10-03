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
('Abuse'),('Alcohol'),('Bullying'),('Bus Concerns'),('Cheating on Schoolwork'),('Cutting/Self-Injury'),('Cyber Bullying'),('Dating Violence'),('Depression'),('Discrimination'),('Dropping Out'),('Drugs'),('Eating Disorder'),('Family Problems'),('Fighting'),('Gangs'),('Health Issues'),('Peer Pressure'),('Pregnancy'),('Run Away'),('School Closing'),('Sexual Harassment'),('Smoking'),('Stealing/Theft'),('Stress'),('Suicide'),('Thank You/Compliment'),('Vandalism'),('Violence'),('Weapons')
$$$

-- NOTE Version unchanged (0.1/11). Default topics only inserted in taicustomer and not in tai upgrade

-- START 1.2

-- $rev 1
alter table tai_topicuser add notify tinyint(4) not null default 1
$$$

update setting set value='1.2/1' where name='_dbtaiversion'
$$$

-- END 1.2/1
