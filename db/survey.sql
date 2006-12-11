
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


