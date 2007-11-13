-- use to upgrade ASP from 9/24/2007 data schema

-- Parent Portal

CREATE TABLE `portalperson` (
  `portaluserid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  PRIMARY KEY  (`portaluserid`,`personid`)
) ENGINE=InnoDB
$$$

CREATE TABLE `portalpersontoken` (
  `token` varchar(255) NOT NULL,
  `expirationdate` datetime NOT NULL,
  `personid` int(11) NOT NULL,
  `creationuserid` int(11) NOT NULL,
  PRIMARY KEY  (`token`),
  UNIQUE KEY `personid` (`personid`)
) ENGINE=InnoDB
$$$

ALTER TABLE `jobtype`
ADD `infoforparents` VARCHAR( 255 ) NOT NULL DEFAULT ''  AFTER `timeslices` ,
ADD `issurvey` TINYINT NOT NULL DEFAULT '0' AFTER `infoforparents`
$$$

CREATE TABLE `jobtypepref` (
`jobtypeid` INT NOT NULL ,
`type` ENUM( 'phone', 'email', 'print', 'sms' ) NOT NULL ,
`sequence` TINYINT NOT NULL ,
`enabled` TINYINT NOT NULL DEFAULT '0',
PRIMARY KEY ( `jobtypeid` , `type` , `sequence` )
) ENGINE = innodb
$$$

CREATE TABLE `contactpref` (
`personid` INT NOT NULL,
`jobtypeid` INT NOT NULL,
`type` ENUM( 'phone', 'email', 'print', 'sms' ) NOT NULL ,
`sequence` TINYINT NOT NULL,
`enabled` TINYINT NOT NULL DEFAULT '0',
PRIMARY KEY ( `personid` , `jobtypeid` , `type` , `sequence` )
) ENGINE = innodb
$$$

ALTER TABLE `reportperson` CHANGE `status` `status` enum('new','queued','assigned','fail','success','duplicate','blocked','nocontacts','declined') NOT NULL
$$$

ALTER TABLE `reportperson`
ADD `numdeclined` tinyint(4) NOT NULL default '0' AFTER `numduperemoved`
$$$


-- alter sms

ALTER TABLE `job`
ADD   `smsmessageid` int(11) default NULL AFTER `printmessageid`
$$$


drop trigger insert_repeating_job
$$$

CREATE TRIGGER insert_repeating_job
AFTER INSERT ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;

IF NEW.status IN ('repeating') THEN
  SELECT value INTO tz FROM setting WHERE name='timezone';

  INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)
         VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.listid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.smsmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, 'repeating', NEW.jobtypeid, NEW.thesql);

  -- copy the jobsettings
  INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;

  -- do not copy schedule because it was inserted via the insert_schedule trigger

END IF;
END
$$$

drop trigger update_job
$$$

CREATE TRIGGER update_job
AFTER UPDATE ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER DEFAULT _$CUSTOMERID_;

SELECT value INTO tz FROM setting WHERE name='timezone';

SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND id=NEW.id;
IF cc = 0 THEN
-- we expect the status to be 'scheduled' when we insert the shard job
-- status 'new' is for jobs that are not yet submitted
  IF NEW.status='scheduled' THEN
    INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)
           VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.listid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.smsmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, NEW.status, NEW.jobtypeid, NEW.thesql);
    -- copy the jobsettings
    INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;
  END IF;
ELSE
-- update job fields
  UPDATE aspshard.qjob SET scheduleid=NEW.scheduleid, phonemessageid=NEW.phonemessageid, emailmessageid=NEW.emailmessageid, printmessageid=NEW.printmessageid, smsmessageid=NEW.smsmessageid, questionnaireid=NEW.questionnaireid, starttime=NEW.starttime, endtime=NEW.endtime, startdate=NEW.startdate, enddate=NEW.enddate, thesql=NEW.thesql WHERE customerid=custid AND id=NEW.id;
  IF NEW.status IN ('processing', 'procactive', 'active', 'cancelling') THEN
    UPDATE aspshard.qjob SET status=NEW.status WHERE customerid=custid AND id=NEW.id;
  END IF;
END IF;
END
$$$


ALTER TABLE `job`
CHANGE `type` `type` set('phone','email','print','sms','survey') NOT NULL default 'phone'
$$$

ALTER TABLE `joblanguage`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL default 'phone'
$$$

ALTER TABLE `message`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL default 'phone'
$$$

ALTER TABLE `reportcontact`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL
$$$

ALTER TABLE `reportperson`
CHANGE `type` `type` enum('phone','email','print','sms') NOT NULL
$$$

ALTER TABLE `messagepart` ADD `maxlen` SMALLINT NULL
$$$

CREATE TABLE `sms` (
  `id` int(11) NOT NULL auto_increment,
  `personid` int(11) NOT NULL default '0',
  `sms` varchar(20) NOT NULL default '',
  `sequence` tinyint(4) NOT NULL default '0',
  `editlock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `personid` (`personid`,`sequence`),
  KEY `dedupe` (`sms`,`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

ALTER TABLE `reportcontact`
ADD `sms` varchar(20) default NULL AFTER `email`
$$$

-- import enhancements

ALTER TABLE `importfield` add `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy' after mapto
$$$

ALTER TABLE `importfield` add `val` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
$$$

ALTER TABLE `importfield` CHANGE `mapfrom` `mapfrom` TINYINT( 4 ) NULL
$$$

-- system setting maxsms
INSERT INTO `setting` (name,value) select 'maxsms', value from setting where name='maxphones'
$$$

-- copy phone fields enabled with sms into new sms records
INSERT INTO `sms` (personid, sequence) select personid, sequence from phone
$$$

INSERT INTO `sms` (personid, sms, sequence) select personid, `phone`, sequence from phone where smsenabled=1 on duplicate key update sms.sms = phone.phone
$$$

ALTER TABLE phone DROP smsenabled
$$$

-- set the jobtype for shard jobs
UPDATE aspshard.qjob qj, job j set qj.jobtypeid=j.jobtypeid where qj.customerid=_$CUSTOMERID_ and qj.id=j.id
$$$

-- if they have 'survey' in the name, convert it to a general survey jobtype
UPDATE jobtype set systempriority=3, issurvey=1 where name like '%survey%'
$$$

-- create new survey jobtype
INSERT INTO jobtype (name, systempriority, issurvey) values ('Survey', 3, 1)
$$$

-- procedure to generate default jobtypepref records for all existing jobtypes
CREATE PROCEDURE test(seq INT, maxval INT, t VARCHAR(10))
BEGIN
  declare enab int default 0;
  label1: LOOP
    SET enab = 0;
    IF seq = 0 THEN SET enab = 1; END IF;
    INSERT INTO jobtypepref (jobtypeid, type, sequence, enabled) select id, t, seq, enab from jobtype;
    INSERT INTO jobtypepref (jobtypeid, type, sequence, enabled) select id, t, seq, enab from jobtype where systempriority=1 on duplicate key update enabled=1;

    SET seq = seq + 1;
    IF seq < maxval THEN ITERATE label1; END IF;
    LEAVE label1;
  END LOOP label1;
END
$$$
call test(0, (select value from setting where name='maxphones'), 'phone');
$$$
call test(0, (select value from setting where name='maxemails'), 'email');
$$$
call test(0, (select value from setting where name='maxsms'), 'sms');
$$$
drop procedure test
$$$

-- drop the priority, only used by gui
ALTER TABLE jobtype DROP `priority`
$$$

-- woops use date rather than datetime
ALTER TABLE `portalpersontoken` CHANGE `expirationdate` `expirationdate` DATE NOT NULL
$$$

-- add curdate and skipheaders to imports
ALTER TABLE `import` ADD `skipheaderlines` TINYINT NOT NULL DEFAULT '0' AFTER `datamodifiedtime`
$$$

ALTER TABLE `importfield` CHANGE `action` `action` ENUM( 'copy', 'staticvalue', 'number', 'currency', 'date', 'lookup', 'curdate' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'copy'
$$$

-- system setting timeslice (from old jobtype)
INSERT INTO `setting` (name,value) values ('_timeslice', 450)
$$$

-- timeslices moved to system setting
ALTER TABLE jobtype DROP `timeslices`
$$$

ALTER TABLE `systemstats` ADD `attempt` TINYINT NOT NULL DEFAULT '0' AFTER `jobid`
$$$

ALTER TABLE `systemstats` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `jobid` , `attempt` , `date` , `hour` )
$$$


