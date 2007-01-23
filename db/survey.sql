
CREATE TABLE `surveyquestionnaire` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `name` varchar(50) collate utf8_bin NOT NULL,
  `description` varchar(50) collate utf8_bin NOT NULL,
  `hasphone` tinyint(4) NOT NULL default '0',
  `hasweb` tinyint(4) NOT NULL default '0',
  `dorandomizeorder` tinyint(4) NOT NULL default '0',
  `machinemessageid` int(11) default NULL,
  `emailmessageid` int(11) default NULL,
  `intromessageid` int(11) default NULL,
  `exitmessageid` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM ;

ALTER TABLE `surveyquestionnaire` ADD `deleted` TINYINT NOT NULL DEFAULT '0';



CREATE TABLE `surveyquestion` (
  `id` int(11) NOT NULL auto_increment,
  `questionnaireid` int(11) NOT NULL,
  `questionnumber` tinyint(4) NOT NULL,
  `webmessage` text collate utf8_bin,
  `phonemessageid` int(11) default NULL,
  `validresponse` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM ;


-- add survey types to job

ALTER TABLE `job` CHANGE `type` `type` SET( 'phone', 'email', 'print', 'surveyemail', 'surveyphone' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'phone';

ALTER TABLE `job` ADD `questionnaireid` INT NULL AFTER `printmessageid` ;

-- now jobworkitem doesn't always need a specific message, for survey the message info is pulled from the questionnaire.
ALTER TABLE `jobworkitem` CHANGE `messageid` `messageid` INT( 11 ) NULL ;


ALTER TABLE `calllog` ADD `resultdata` TEXT NULL AFTER `callattempt` ;

CREATE TABLE `surveyresponse` (
`jobid` INT NOT NULL ,
`questionnumber` TINYINT NOT NULL ,
`answer` TINYINT NOT NULL ,
`tally` INT NOT NULL DEFAULT '0'
) ENGINE = MYISAM ;

ALTER TABLE `surveyresponse` ADD PRIMARY KEY ( `jobid` , `questionnumber` , `answer` ) ;


ALTER TABLE `calllog` ADD `participated` TINYINT NOT NULL DEFAULT '0' AFTER `resultdata` ;

ALTER TABLE `surveyquestion` ADD `reportlabel` VARCHAR( 30 ) AFTER `phonemessageid` ;


CREATE TABLE `surveyemailcode` (
  `code` char(22) character set ascii collate ascii_bin NOT NULL,
  `jobworkitemid` bigint(20) NOT NULL,
  `customerid` int(11) NOT NULL,
  `isused` tinyint(4) NOT NULL default '0',
  `dateused` datetime default NULL,
  `loggedip` varchar(15) collate utf8_bin default NULL,
  PRIMARY KEY  (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `surveyemailcode` ADD INDEX ( `jobworkitemid` ) ;

ALTER TABLE `surveyquestionnaire` ADD `webpagetitle` VARCHAR( 50 ) NULL AFTER `exitmessageid` ;
ALTER TABLE `surveyquestionnaire` ADD `webexitmessage` TEXT NULL AFTER `webpagetitle` ;

ALTER TABLE `surveyquestionnaire` ADD `usehtml` TINYINT NOT NULL DEFAULT '0' AFTER `webexitmessage` ;


ALTER TABLE `surveyemailcode` ADD `resultdata` TEXT NOT NULL AFTER `loggedip` ;



ALTER TABLE `surveyemailcode` DROP INDEX `jobworkitemid` ,
ADD UNIQUE `jobworkitemid` ( `jobworkitemid` ) ;