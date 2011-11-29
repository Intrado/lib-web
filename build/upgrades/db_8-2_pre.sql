-- $rev1

INSERT INTO fieldmap (id, fieldnum, name, options) VALUES 
	(NULL , '$d01', '%Date%', 'text,systemvar'),
	(NULL , '$d02', '%Tomorrow\'s Date%', 'text,systemvar'),
	(NULL , '$d03', '%Yesterday\'s Date%', 'text,systemvar');
	
	
-- $rev 2

CREATE TABLE `monitor` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`userid` int(11) NOT NULL,
	`type` enum('job-active','job-firstpass','job-complete') NOT NULL,
	`action` enum('email') NOT NULL DEFAULT 'email',
	PRIMARY KEY (`id`),
	KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8;


CREATE TABLE `monitorfilter` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`monitorid` int(11) NOT NULL,
	`type` enum('userid','jobtypeid') NOT NULL,
	`val` text,
	PRIMARY KEY (`id`),
	KEY `monitorid` (`monitorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
