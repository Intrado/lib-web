-- $rev1

INSERT INTO fieldmap (id, fieldnum, name, options) VALUES 
	(NULL , '$d01', '%Date%', 'text,systemvar'),
	(NULL , '$d02', '%Tomorrow\'s Date%', 'text,systemvar'),
	(NULL , '$d03', '%Yesterday\'s Date%', 'text,systemvar')
$$$
	
	
-- $rev 2

CREATE TABLE `monitor` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`userid` int(11) NOT NULL,
	`type` enum('job-active','job-firstpass','job-complete') NOT NULL,
	`action` enum('email') NOT NULL DEFAULT 'email',
	PRIMARY KEY (`id`),
	KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8
$$$


CREATE TABLE `monitorfilter` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`monitorid` int(11) NOT NULL,
	`type` enum('userid','jobtypeid') NOT NULL,
	`val` text,
	PRIMARY KEY (`id`),
	KEY `monitorid` (`monitorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
$$$

-- $rev 3

ALTER TABLE `surveyquestion` CHANGE `questionnumber` `questionnumber` INT NOT NULL 
$$$

ALTER TABLE `surveyresponse` CHANGE `questionnumber` `questionnumber` INT NOT NULL 
$$$

-- $rev 4

-- data is always base64, only need ascii. update to longtext for files > 16m
ALTER TABLE `content` CHANGE `data` `data` LONGTEXT CHARACTER SET ascii COLLATE ascii_bin NOT NULL 
$$$

-- $rev 5

CREATE TABLE `feedcategory` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 `description` TEXT NOT NULL,
 `deleted` tinyint(4) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `userfeedcategory` (
 `userid` int(11) NOT NULL,
 `feedcategoryid` int(11) NOT NULL,
 PRIMARY KEY (`userid`,`feedcategoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `jobpost` CHANGE `type` `type` ENUM( 'facebook', 'twitter', 'page', 'feed' ) NOT NULL 
$$$

-- $rev 6
-- nothing, create monitor template moved to rev9

-- $rev 7
CREATE TABLE `authorizedcallerid` (
	`callerid` varchar(20),
	PRIMARY KEY (`callerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

CREATE TABLE `authorizedusercallerid` (
	`userid` int(11) NOT NULL,
	`callerid` varchar(20) NOT NULL,
	PRIMARY KEY (`userid`,`callerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
$$$

-- $rev 8
-- update SM access profile

-- $rev 9
-- create monitor template, and any missing templates from pre 7.8

-- $rev 10

ALTER TABLE `job` DROP INDEX `startdate`
$$$

-- index for feed generator
ALTER TABLE `job` ADD INDEX `startdate` ( `startdate` , `starttime` ) 
$$$

