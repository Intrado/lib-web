CREATE TABLE access (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE address (
  id int(11) NOT NULL auto_increment,
  personid int(11) NOT NULL default '0',
  addressee varchar(50) default NULL,
  addr1 varchar(50) default NULL,
  addr2 varchar(50) default NULL,
  city varchar(50) default NULL,
  state char(2) default NULL,
  zip varchar(10) default NULL,
  editlock tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY personid (personid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE audiofile (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  contentid bigint(20) NOT NULL default '0',
  recorddate datetime default NULL,
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY list (userid,deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE blockednumber (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  description varchar(100) NOT NULL,
  pattern varchar(10) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE content (
  id bigint(20) NOT NULL auto_increment,
  contenttype varchar(255) NOT NULL default '',
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE email (
  id int(11) NOT NULL auto_increment,
  personid int(11) NOT NULL default '0',
  email varchar(100) NOT NULL default '',
  sequence tinyint(4) NOT NULL default '0',
  editlock tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY personid (personid,sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE fieldmap (
  id int(11) NOT NULL auto_increment,
  fieldnum varchar(4) NOT NULL default '0',
  `name` varchar(20) NOT NULL default '',
  options text NOT NULL,
  PRIMARY KEY  (id),
  KEY getfieldname (fieldnum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$


CREATE TABLE `import` (
  `id` int(11) NOT NULL auto_increment,
  `uploadkey` varchar(255) default NULL,
  `scheduleid` int(11) default NULL,
  `userid` int(11) NOT NULL default '0',
  `listid` int(11) default NULL,
  `name` varchar(50) NOT NULL default '',
  `description` varchar(50) NOT NULL default '',
  `status` enum('idle','queued','running','error') NOT NULL default 'idle',
  `type` enum('manual','automatic','list','addressbook') NOT NULL default 'manual',
  `ownertype` enum('system','user') NOT NULL default 'system',
  `updatemethod` enum('updateonly','update','full') NOT NULL default 'full',
  `lastrun` datetime default NULL,
  `data` longblob default NULL,
  `datamodifiedtime` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uploadkey` (`uploadkey`),
  KEY `scheduleid` (`scheduleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE importfield (
  id int(11) NOT NULL auto_increment,
  importid int(11) NOT NULL default '0',
  mapto varchar(4) NOT NULL default '',
  mapfrom tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE importjob (
  id int(11) NOT NULL auto_increment,
  importid int(11) NOT NULL,
  jobid int(11) NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE job (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  scheduleid int(11) default NULL,
  jobtypeid int(11) default '0',
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  listid int(11) NOT NULL default '0',
  phonemessageid int(11) default NULL,
  emailmessageid int(11) default NULL,
  printmessageid int(11) default NULL,
  questionnaireid int(11) default NULL,
  `type` set('phone','email','print','survey') NOT NULL default 'phone',
  createdate datetime NOT NULL default '0000-00-00 00:00:00',
  startdate date NOT NULL default '0000-00-00',
  enddate date NOT NULL default '2006-07-04',
  starttime time NOT NULL default '00:00:00',
  endtime time NOT NULL default '00:00:00',
  finishdate datetime default NULL,
  `status` enum('new','processing','active','complete','cancelled','cancelling','repeating') NOT NULL default 'new',
  deleted tinyint(4) NOT NULL default '0',
  ranautoreport tinyint(4) NOT NULL default '0',
  priorityadjust int(11) NOT NULL default '0',
  cancelleduserid int(11) default NULL,
  `thesql` text,
  PRIMARY KEY  (id),
  KEY `status` (`status`,id),
  KEY startdate (startdate),
  KEY enddate (enddate),
  KEY starttime (starttime),
  KEY endtime (endtime),
  KEY startdate_2 (startdate,enddate,starttime,endtime,id),
  KEY scheduleid (scheduleid),
  KEY ranautoreport (ranautoreport,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE joblanguage (
  id int(11) NOT NULL auto_increment,
  jobid int(11) NOT NULL default '0',
  messageid int(11) NOT NULL default '0',
  `type` enum('phone','email','print') NOT NULL default 'phone',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY jobid (jobid,`language`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `jobsetting` (
  `jobid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

CREATE TABLE jobtype (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  priority int(11) NOT NULL default '10000',
  systempriority tinyint(4) NOT NULL default '3',
  timeslices smallint(6) NOT NULL default '0',
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY customerid (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `language` (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE list (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  lastused datetime default NULL,
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY userid (userid,`name`,deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE listentry (
  id int(11) NOT NULL auto_increment,
  listid int(11) NOT NULL default '0',
  `type` enum('R','A','N') NOT NULL default 'A',
  ruleid int(11) default NULL,
  personid int(11) default NULL,
  PRIMARY KEY  (id),
  KEY `type` (personid,listid,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE message (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  `type` enum('phone','email','print') NOT NULL default 'phone',
  `data` text NOT NULL,
  lastused datetime default NULL,
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY userid (userid,`type`,deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE messagepart (
  id int(11) NOT NULL auto_increment,
  messageid int(11) NOT NULL default '0',
  `type` enum('A','T','V') NOT NULL default 'A',
  audiofileid int(11) default NULL,
  txt text,
  fieldnum varchar(4) default NULL,
  defaultvalue varchar(255) default NULL,
  voiceid int(11) default NULL,
  sequence tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY messageid (messageid,sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE permission (
  id int(11) NOT NULL auto_increment,
  accessid int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE person (
  id int(11) NOT NULL auto_increment,
  userid int(11) default NULL,
  pkey varchar(255) default NULL,
  importid int(11) default NULL,
  lastimport datetime default NULL,
  `type` enum('system','addressbook','manualadd','upload') NOT NULL default 'system',
  deleted tinyint(4) NOT NULL default '0',
  f01 varchar(50) NOT NULL,
  f02 varchar(50) NOT NULL,
  f03 varchar(50) NOT NULL,
  f04 varchar(255) NOT NULL,
  f05 varchar(255) NOT NULL,
  f06 varchar(255) NOT NULL,
  f07 varchar(255) NOT NULL,
  f08 varchar(255) NOT NULL,
  f09 varchar(255) NOT NULL,
  f10 varchar(255) NOT NULL,
  f11 varchar(255) NOT NULL,
  f12 varchar(255) NOT NULL,
  f13 varchar(255) NOT NULL,
  f14 varchar(255) NOT NULL,
  f15 varchar(255) NOT NULL,
  f16 varchar(255) NOT NULL,
  f17 varchar(255) NOT NULL,
  f18 varchar(255) NOT NULL,
  f19 varchar(255) NOT NULL,
  f20 varchar(255) NOT NULL,
  PRIMARY KEY  (id),
  KEY getbykey (pkey(50)),
  KEY pkeysort (id,pkey(50)),
  KEY pkeysortb (pkey(50),id),
  KEY lastimport (importid,lastimport),
  KEY general (id,deleted),
  KEY ownership (userid),
  KEY namesort (f02,f01),
  KEY lang (f03),
  KEY f04 (f04(20)),
  KEY f05 (f05(20)),
  KEY f06 (f06(20)),
  KEY f07 (f07(20)),
  KEY f08 (f08(20)),
  KEY f09 (f09(20)),
  KEY f10 (f10(20)),
  KEY f11 (f11(20)),
  KEY f12 (f12(20)),
  KEY f13 (f13(20)),
  KEY f14 (f14(20)),
  KEY f15 (f15(20)),
  KEY f16 (f16(20)),
  KEY f17 (f17(20)),
  KEY f18 (f18(20)),
  KEY f19 (f19(20)),
  KEY f20 (f20(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE persondatavalues (
  id int(11) NOT NULL auto_increment,
  fieldnum varchar(4) NOT NULL default '',
  `value` varchar(255) default NULL,
  refcount int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY valuelookup (`value`(50)),
  KEY `name` (fieldnum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE phone (
  id int(11) NOT NULL auto_increment,
  personid int(11) NOT NULL default '0',
  phone varchar(20) NOT NULL default '',
  sequence tinyint(4) NOT NULL default '0',
  editlock tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY personid (personid,sequence),
  KEY dedupe (phone,sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE reportcontact (
  jobid int(11) NOT NULL,
  personid int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  sequence tinyint(4) NOT NULL,
  numattempts tinyint(4) NOT NULL,
  userid int(11) NOT NULL,
  starttime bigint(20) NOT NULL default '0',
  result enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted','notattempted') NOT NULL,
  participated tinyint(4) NOT NULL default '0',
  duration float default NULL,
  resultdata text,
  attemptdata varchar(255) default NULL,
  phone varchar(20) default NULL,
  email varchar(100) default NULL,
  addressee varchar(50) default NULL,
  addr1 varchar(50) default NULL,
  addr2 varchar(50) default NULL,
  city varchar(50) default NULL,
  state char(2) default NULL,
  zip varchar(10) default NULL,
  PRIMARY KEY  (jobid,`type`,personid,sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE reportperson (
  jobid int(11) NOT NULL,
  personid int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  userid int(11) NOT NULL,
  messageid int(11),
  `status` enum('new','queued','assigned','fail','success','duplicate','blocked') NOT NULL,
  numcontacts tinyint(4) NOT NULL,
  numduperemoved tinyint(4) NOT NULL,
  numblocked tinyint(4) NOT NULL,
  pkey varchar(255) default NULL,
  f01 varchar(50) NOT NULL default '',
  f02 varchar(50) NOT NULL default '',
  f03 varchar(50) NOT NULL default '',
  f04 varchar(255) NOT NULL default '',
  f05 varchar(255) NOT NULL default '',
  f06 varchar(255) NOT NULL default '',
  f07 varchar(255) NOT NULL default '',
  f08 varchar(255) NOT NULL default '',
  f09 varchar(255) NOT NULL default '',
  f10 varchar(255) NOT NULL default '',
  f11 varchar(255) NOT NULL default '',
  f12 varchar(255) NOT NULL default '',
  f13 varchar(255) NOT NULL default '',
  f14 varchar(255) NOT NULL default '',
  f15 varchar(255) NOT NULL default '',
  f16 varchar(255) NOT NULL default '',
  f17 varchar(255) NOT NULL default '',
  f18 varchar(255) NOT NULL default '',
  f19 varchar(255) NOT NULL default '',
  f20 varchar(255) NOT NULL default '',
  PRIMARY KEY  (jobid,`type`,personid),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE rule (
  id int(11) NOT NULL auto_increment,
  logical enum('and','or','and not','or not') NOT NULL default 'and',
  fieldnum varchar(4) NOT NULL default '0',
  op enum('eq','ne','gt','ge','lt','le','lk','sw','ew','cn','in','reldate') NOT NULL default 'eq',
  val text NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE schedule (
  id int(11) NOT NULL auto_increment,
  userid int(11) default NULL,
  triggertype enum('import','job') NOT NULL default 'import',
  `type` enum('R','O') NOT NULL default 'R',
  `dow` varchar(20) NOT NULL default '',
  `time` time NOT NULL default '00:00:00',
  nextrun datetime default NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE setting (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY lookup (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$$

CREATE TABLE specialtask (
  id bigint(20) NOT NULL auto_increment,
  `status` enum('new','queued','assigned','done') NOT NULL,
  `type` varchar(50) NOT NULL default 'EasyCall',
  `data` text NOT NULL,
  lastcheckin datetime default NULL,
  PRIMARY KEY  (id),
  KEY `status` (`status`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `surveyweb` (
`code` CHAR( 22 ) CHARACTER SET ascii COLLATE ascii_bin NOT NULL ,
`jobid` INT( 11 ) NOT NULL ,
`personid` INT( 11 ) NOT NULL ,
`status` ENUM( 'noresponse', 'web', 'phone' ) NOT NULL ,
`dateused` DATETIME NULL ,
`loggedip` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_bin NULL ,
`resultdata` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
PRIMARY KEY (jobid, personid)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
$$$

CREATE TABLE surveyquestion (
  id int(11) NOT NULL auto_increment,
  questionnaireid int(11) NOT NULL,
  questionnumber tinyint(4) NOT NULL,
  webmessage text character set utf8 collate utf8_bin,
  phonemessageid int(11) default NULL,
  reportlabel varchar(30) default NULL,
  validresponse tinyint(4) NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE surveyquestionnaire (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL,
  `name` varchar(50) character set utf8 collate utf8_bin NOT NULL,
  description varchar(50) character set utf8 collate utf8_bin NOT NULL,
  hasphone tinyint(4) NOT NULL default '0',
  hasweb tinyint(4) NOT NULL default '0',
  dorandomizeorder tinyint(4) NOT NULL default '0',
  machinemessageid int(11) default NULL,
  emailmessageid int(11) default NULL,
  intromessageid int(11) default NULL,
  exitmessageid int(11) default NULL,
  webpagetitle varchar(50) default NULL,
  webexitmessage text,
  usehtml tinyint(4) NOT NULL default '0',
  leavemessage tinyint(4) NOT NULL default '0',
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE surveyresponse (
  jobid int(11) NOT NULL,
  questionnumber tinyint(4) NOT NULL,
  answer tinyint(4) NOT NULL,
  tally int(11) NOT NULL default '0',
  PRIMARY KEY  (jobid,questionnumber,answer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE ttsvoice (
  id int(11) NOT NULL auto_increment,
  `language` varchar(20) NOT NULL default '',
  gender enum('male','female') NOT NULL default 'male',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `user` (
  id int(11) NOT NULL auto_increment,
  accessid int(11) NOT NULL default '0',
  login varchar(20) character set utf8 collate utf8_bin NOT NULL,
  `password` varchar(255) NOT NULL default '',
  accesscode varchar(10) NOT NULL default '',
  pincode varchar(255) NOT NULL default '',
  firstname varchar(50) NOT NULL default '',
  lastname varchar(50) NOT NULL default '',
  phone varchar(20) NOT NULL default '',
  email text NOT NULL,
  enabled tinyint(4) NOT NULL default '0',
  lastlogin datetime default NULL,
  deleted tinyint(4) NOT NULL default '0',
  ldap tinyint(10) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY login (login,`password`,enabled,deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE userjobtypes (
  userid int(11) NOT NULL default '0',
  jobtypeid int(11) NOT NULL default '0',
  PRIMARY KEY  (userid,jobtypeid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE userrule (
  userid int(11) NOT NULL default '0',
  ruleid int(11) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE usersetting (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (id),
  KEY usersetting (userid,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE voicereply (
  id int(11) NOT NULL auto_increment,
  personid int(11) NOT NULL,
  jobid int(11) NOT NULL,
  userid int(11) NOT NULL,
  contentid bigint(20) NOT NULL,
  replytime bigint(20) NOT NULL,
  listened tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY jobid (jobid),
  KEY userid (userid),
  KEY replytime (replytime)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

CREATE TABLE `systemstats` (
`datetime` BIGINT(20) NOT NULL ,
`answered` INT NOT NULL ,
`machine` INT NOT NULL ,
`busy` INT NOT NULL ,
`noanswer` INT NOT NULL ,
PRIMARY KEY ( `datetime` )
) ENGINE = innodb
$$$

CREATE TABLE `jobstats` (
`jobid` INT NOT NULL ,
`count` INT NOT NULL ,
PRIMARY KEY ( `jobid` )
) ENGINE = innodb
$$$


CREATE TABLE `smsjob` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `listid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(50) NOT NULL,
  `txt` varchar(160) NOT NULL,
  `sendoptout` tinyint(4) NOT NULL,
  `sentdate` datetime NOT NULL,
  `status` enum('new','queued','sent','error') NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
$$$



CREATE TABLE `smsmsg` (
  `id` int(11) NOT NULL auto_increment,
  `smsjobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `phone` varchar(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
$$$

ALTER TABLE `phone` ADD `smsenabled` TINYINT NOT NULL DEFAULT '0';
$$$

ALTER TABLE `blockednumber` ADD `type` ENUM( 'call', 'sms', 'both' ) NOT NULL DEFAULT 'both';
$$$

ALTER TABLE `smsjob` ADD `deleted` TINYINT NOT NULL DEFAULT '0';
$$$

ALTER TABLE `systemstats` DROP `datetime`;
$$$

ALTER TABLE `systemstats` ADD `date` DATE NOT NULL FIRST ,
ADD `hour` INT( 11 ) NOT NULL AFTER `date` ;
$$$
ALTER TABLE `systemstats` ADD PRIMARY KEY ( `date` , `hour` ) ;
$$$

CREATE TABLE reportinstance (
  ID int(11) NOT NULL auto_increment,
  Parameters text NOT NULL,
  Fields text NULL,
  Activefields text NULL,
  PRIMARY KEY  (ID)
) TYPE=InnoDB;
$$$

CREATE TABLE `reportsubscription` (
  `ID` int(11) NOT NULL auto_increment,
  `UserID` int(11) NOT NULL default '0',
  `Name` varchar(20) NOT NULL default '',
  `ReportInstanceID` int(11) NOT NULL default '0',
  `Dow` varchar(255) NOT NULL default '',
  `Dom` tinyint(4) NOT NULL default '0',
  `Date` date NOT NULL default '0000-00-00',
  `NextRun` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`ID`),
  KEY `subscription` (`UserID`,`ReportInstanceID`)
) TYPE=InnoDB;
$$$

-- triggers from customer database to shard database

CREATE TRIGGER insert_repeating_job
AFTER INSERT ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER;

IF NEW.status IN ('repeating') THEN
  SELECT value INTO tz FROM setting WHERE name='timezone';
  SELECT value INTO custid FROM setting WHERE name='_customerid';

  INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, thesql)
         VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.listid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, 'repeating', NEW.thesql);

  -- copy the jobsettings
  INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;

  -- copy schedule
  INSERT INTO aspshard.qschedule (id, customerid, dow, time, nextrun) SELECT id, custid, dow, time, nextrun FROM schedule WHERE id=NEW.scheduleid;

END IF;
END
$$$


CREATE TRIGGER update_job
AFTER UPDATE ON job FOR EACH ROW
BEGIN
DECLARE cc INTEGER;
DECLARE tz VARCHAR(50);
DECLARE custid INTEGER;

SELECT value INTO custid FROM setting WHERE name='_customerid';
SELECT value INTO tz FROM setting WHERE name='timezone';

SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND id=NEW.id;
IF cc = 0 THEN
-- we expect the status to be 'processing' when we insert the shard job
-- status 'new' is for jobs that are not yet submitted
  IF NEW.status IN ('processing') THEN
    INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, jobtypeid, listid, phonemessageid, emailmessageid, printmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, thesql)
           VALUES(NEW.id, custid, NEW.userid, NEW.scheduleid, NEW.jobtypeid, NEW.listid, NEW.phonemessageid, NEW.emailmessageid, NEW.printmessageid, NEW.questionnaireid, tz, NEW.startdate, NEW.enddate, NEW.starttime, NEW.endtime, 'new', NEW.thesql);
    -- copy the jobsettings
    INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT custid, NEW.id, name, value FROM jobsetting WHERE jobid=NEW.id;
  END IF;
ELSE
-- update job fields
  UPDATE aspshard.qjob SET phonemessageid=NEW.phonemessageid, emailmessageid=NEW.emailmessageid, printmessageid=NEW.printmessageid, questionnaireid=NEW.questionnaireid, starttime=NEW.starttime, endtime=NEW.endtime, startdate=NEW.startdate, enddate=NEW.enddate, thesql=NEW.thesql WHERE customerid=custid AND id=NEW.id;
  IF NEW.status IN ('active', 'cancelling') THEN
    UPDATE aspshard.qjob SET status=NEW.status WHERE customerid=custid AND id=NEW.id;
  END IF;
  IF NEW.status IN ('cancelling') THEN
    -- remove jobtasks that have not begun
    DELETE FROM aspshard.qjobtask WHERE customerid=custid AND jobid=NEW.id AND status IN ('active','pending','waiting');
  END IF;
  IF NEW.status IN ('complete', 'cancelled') THEN
    DELETE FROM aspshard.qjob WHERE customerid=custid AND id=NEW.id;
    DELETE FROM aspshard.qjobtask WHERE customerid=custid AND jobid=NEW.id;
    DELETE FROM aspshard.qjobsetting WHERE customerid=custid AND jobid=NEW.id;
  END IF;
END IF;
END
$$$

CREATE TRIGGER delete_job
AFTER DELETE ON job FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
SELECT value INTO custid FROM setting WHERE name='_customerid';
-- only repeating jobs ever get deleted
    DELETE FROM aspshard.qjob WHERE customerid=custid AND id=OLD.id;
    DELETE FROM aspshard.qjobsetting WHERE customerid=custid AND jobid=OLD.id;
END
$$$

CREATE TRIGGER insert_jobsetting
AFTER INSERT ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
DECLARE cc INTEGER;

SELECT value INTO custid FROM setting WHERE name='_customerid';

-- the job must be inserted before the settings
SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND id=NEW.jobid;
IF cc = 1 THEN
    INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) VALUES (custid, NEW.jobid, NEW.name, NEW.value);
END IF;
END
$$$

CREATE TRIGGER update_jobsetting
AFTER UPDATE ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
SELECT value INTO custid FROM setting WHERE name='_customerid';

    UPDATE aspshard.qjobsetting SET value=NEW.value WHERE customerid=custid AND jobid=NEW.jobid AND name=NEW.name;
END
$$$

CREATE TRIGGER delete_jobsetting
AFTER DELETE ON jobsetting FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
SELECT value INTO custid FROM setting WHERE name='_customerid';

    DELETE FROM aspshard.qjobsetting WHERE customerid=custid AND jobid=OLD.jobid AND name=OLD.name;
END
$$$

CREATE TRIGGER insert_schedule
AFTER INSERT ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
DECLARE cc INTEGER;

SELECT value INTO custid FROM setting WHERE name='_customerid';

-- the job must be inserted before the schedule
SELECT COUNT(*) INTO cc FROM aspshard.qjob WHERE customerid=custid AND scheduleid=NEW.id;
IF cc = 1 THEN
    INSERT INTO aspshard.qschedule (id, customerid, dow, time, nextrun) VALUES (NEW.id, custid, NEW.dow, NEW.time, NEW.nextrun);
END IF;
END
$$$

CREATE TRIGGER update_schedule
AFTER UPDATE ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
SELECT value INTO custid FROM setting WHERE name='_customerid';

IF (OLD.dow <> NEW.dow ||
    OLD.time <> NEw.time ||
    OLD.nextrun <> NEW.nextrun) THEN
    UPDATE aspshard.qschedule SET dow=NEW.dow, time=NEW.time, nextrun=NEW.nextrun WHERE id=NEW.id AND customerid=custid;
END IF;
END
$$$

CREATE TRIGGER delete_schedule
AFTER DELETE ON schedule FOR EACH ROW
BEGIN
DECLARE custid INTEGER;
SELECT value INTO custid FROM setting WHERE name='_customerid';

    DELETE FROM aspshard.qschedule WHERE id=OLD.id AND customerid=custid;
END
$$$

create procedure start_import( in_importid int)
begin
declare l_custid int;
select value+0 from setting where name='_customerid' into l_custid;
insert ignore into aspshard.importqueue (customerid,localimportid) values (l_custid,in_importid);
end
$$$


