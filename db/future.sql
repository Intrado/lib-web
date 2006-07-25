
CREATE TABLE `jobstats` (
  `id` int(11) NOT NULL auto_increment,
  `customerid` int(11) NOT NULL default '0',
  `jobid` int(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` double NOT NULL default '0',
  `format` enum('int','percent','float') NOT NULL default 'int',
  `sequence` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `byname` (`jobid`,`name`(20)),
  KEY `bysequence` (`jobid`,`sequence`)
) TYPE=MyISAM;


CREATE TABLE `monitor` (
  `id` int(11) NOT NULL auto_increment,
  `parentid` int(11) default NULL,
  `customerid` int(11) NOT NULL default '0',
  `type` varchar(20) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `options` text NOT NULL,
  `lastcheckin` datetime default NULL,
  `deleted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `general` (`type`,`name`,`lastcheckin`)
) TYPE=MyISAM;



ALTER TABLE `job` ADD `surveymode` TINYINT DEFAULT '0' NOT NULL AFTER `status` ;

CREATE TABLE `surveyresult` (
	`id` INT NOT NULL AUTO_INCREMENT ,
	`jobtaskid` INT NOT NULL ,
	`questionnumber` TINYINT NOT NULL ,
	`response` TINYINT NOT NULL ,
	PRIMARY KEY ( `id` ) ,
	INDEX ( `jobtaskid` , `questionnumber` )
) TYPE = MYISAM ;


CREATE TABLE `survey` (
`id` INT NOT NULL AUTO_INCREMENT ,
`jobid` INT NOT NULL ,
PRIMARY KEY ( `id` ) ,
INDEX ( `jobid` )
) TYPE = MYISAM ;



CREATE TABLE `surveyquestion` (
`id` INT NOT NULL ,
`surveyid` INT NOT NULL ,
`questionnumber` TINYINT NOT NULL ,
`messageid` INT NOT NULL ,
`description` TEXT NOT NULL ,
`valid` VARCHAR( 10 ) NOT NULL
) TYPE = MYISAM ;


ALTER TABLE `survey` ADD `dophone` TINYINT DEFAULT '0' NOT NULL AFTER `jobid` ,
ADD `doweb` TINYINT DEFAULT '0' NOT NULL AFTER `dophone` ,


ALTER TABLE `message` CHANGE `type` `type` ENUM( 'phone', 'email', 'print', 'survey' ) NOT NULL DEFAULT 'phone'


CREATE TABLE `surveyemaillink` (
`id` INT NOT NULL AUTO_INCREMENT ,
`jobworkitemid` INT NOT NULL ,
`linkcode` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
INDEX ( `linkcode` )
) TYPE = MYISAM ;


ALTER TABLE `surveyresult` CHANGE `jobtaskid` `jobworkitemid` INT( 11 ) NOT NULL DEFAULT '0'

ALTER TABLE `surveyresult` ADD `jobid` INT NOT NULL AFTER `id` ;

ALTER TABLE `surveyresult` ADD INDEX ( `jobid` ) ;


ALTER TABLE `surveyquestion` ADD PRIMARY KEY ( `id` ) 


ALTER TABLE `surveyquestion` ADD `dotts` TINYINT DEFAULT '0' NOT NULL AFTER `messageid` ;


-- need table linking repeating job id and import id. when import is done, checks and fires off these jobs.



