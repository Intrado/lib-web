CREATE TABLE access (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE blockednumber (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  description varchar(100) NOT NULL,
  pattern varchar(10) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE content (
  id bigint(20) NOT NULL auto_increment,
  contenttype varchar(255) NOT NULL default '',
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE email (
  id int(11) NOT NULL auto_increment,
  personid int(11) NOT NULL default '0',
  email varchar(100) NOT NULL default '',
  sequence tinyint(4) NOT NULL default '0',
  editlock tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY personid (personid,sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE fieldmap (
  id int(11) NOT NULL auto_increment,
  fieldnum varchar(4) NOT NULL default '0',
  `name` varchar(20) NOT NULL default '',
  options text NOT NULL,
  PRIMARY KEY  (id),
  KEY getfieldname (fieldnum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `import` (
  id int(11) NOT NULL auto_increment,
  uploadkey varchar(255) default NULL,
  userid int(11) NOT NULL default '0',
  listid int(11) default NULL,
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  `status` enum('idle','running','error') NOT NULL default 'idle',
  `type` enum('manual','automatic','list','addressbook') NOT NULL default 'manual',
  path text,
  scheduleid int(11) default NULL,
  ownertype enum('system','user') NOT NULL default 'system',
  updatemethod enum('updateonly','update','full') NOT NULL default 'full',
  lastrun datetime default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uploadkey (uploadkey),
  KEY scheduleid (scheduleid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE importfield (
  id int(11) NOT NULL auto_increment,
  importid int(11) NOT NULL default '0',
  mapto varchar(4) NOT NULL default '',
  mapfrom tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE importjob (
  id int(11) NOT NULL auto_increment,
  importid int(11) NOT NULL,
  jobid int(11) NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `status` enum('new','active','complete','cancelled','cancelling','repeating') NOT NULL default 'new',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE joblanguage (
  id int(11) NOT NULL auto_increment,
  jobid int(11) NOT NULL default '0',
  messageid int(11) NOT NULL default '0',
  `type` enum('phone','email','print') NOT NULL default 'phone',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY jobid (jobid,`language`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `jobsetting` (
  `jobid` bigint(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE jobtype (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  priority int(11) NOT NULL default '10000',
  systempriority tinyint(4) NOT NULL default '3',
  timeslices smallint(6) NOT NULL default '0',
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY customerid (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `language` (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE list (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  description varchar(50) NOT NULL default '',
  lastused datetime default NULL,
  deleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY userid (userid,`name`,deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE listentry (
  id int(11) NOT NULL auto_increment,
  listid int(11) NOT NULL default '0',
  `type` enum('R','A','N') NOT NULL default 'A',
  ruleid int(11) default NULL,
  personid int(11) default NULL,
  PRIMARY KEY  (id),
  KEY `type` (personid,listid,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE permission (
  id int(11) NOT NULL auto_increment,
  accessid int(11) NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` text NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE persondatavalues (
  id int(11) NOT NULL auto_increment,
  fieldnum varchar(4) NOT NULL default '',
  `value` varchar(255) default NULL,
  refcount int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY valuelookup (`value`(50)),
  KEY `name` (fieldnum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE phone (
  id int(11) NOT NULL auto_increment,
  personid int(11) NOT NULL default '0',
  phone varchar(20) NOT NULL default '',
  sequence tinyint(4) NOT NULL default '0',
  editlock tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY personid (personid,sequence),
  KEY dedupe (phone,sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE reportcontact (
  jobid int(11) NOT NULL,
  personid int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  sequence tinyint(4) NOT NULL,
  numattempts tinyint(4) NOT NULL,
  userid int(11) NOT NULL,
  starttime bigint(20) NOT NULL default '0',
  result enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted') NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE reportperson (
  jobid int(11) NOT NULL,
  personid int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  userid int(11) NOT NULL,
  messageid int(11) NOT NULL,
  `status` enum('new','queued','assigned','fail','success','duplicate','blocked') NOT NULL,
  numcontacts tinyint(4) NOT NULL,
  numduperemoved tinyint(4) NOT NULL,
  numblocked tinyint(4) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE rule (
  id int(11) NOT NULL auto_increment,
  logical enum('and','or','and not','or not') NOT NULL default 'and',
  fieldnum varchar(4) NOT NULL default '0',
  op enum('eq','ne','gt','ge','lt','le','lk','sw','ew','cn','in','reldate') NOT NULL default 'eq',
  val text NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE schedule (
  id int(11) NOT NULL auto_increment,
  userid int(11) default NULL,
  triggertype enum('import','job') NOT NULL default 'import',
  `type` enum('R','O') NOT NULL default 'R',
  `time` time NOT NULL default '00:00:00',
  nextrun datetime default NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE scheduleday (
  id int(11) NOT NULL auto_increment,
  scheduleid int(11) NOT NULL default '0',
  dow tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY scheduleid (scheduleid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE setting (
  id int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY lookup (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE specialtask (
  id bigint(20) NOT NULL auto_increment,
  `status` enum('new','queued','assigned','done') NOT NULL,
  `type` varchar(50) NOT NULL default 'EasyCall',
  `data` text NOT NULL,
  lastcheckin datetime default NULL,
  PRIMARY KEY  (id),
  KEY `status` (`status`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE surveyemailcode (
  `code` char(22) character set ascii collate ascii_bin NOT NULL,
  jobworkitemid bigint(20) NOT NULL,
  isused tinyint(4) NOT NULL default '0',
  dateused datetime default NULL,
  loggedip varchar(15) collate utf8_bin default NULL,
  resultdata text collate utf8_bin NOT NULL,
  PRIMARY KEY  (`code`),
  UNIQUE KEY jobworkitemid (jobworkitemid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE surveyquestion (
  id int(11) NOT NULL auto_increment,
  questionnaireid int(11) NOT NULL,
  questionnumber tinyint(4) NOT NULL,
  webmessage text character set utf8 collate utf8_bin,
  phonemessageid int(11) default NULL,
  reportlabel varchar(30) default NULL,
  validresponse tinyint(4) NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE surveyresponse (
  jobid int(11) NOT NULL,
  questionnumber tinyint(4) NOT NULL,
  answer tinyint(4) NOT NULL,
  tally int(11) NOT NULL default '0',
  PRIMARY KEY  (jobid,questionnumber,answer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ttsvoice (
  id int(11) NOT NULL auto_increment,
  `language` varchar(20) NOT NULL default '',
  gender enum('male','female') NOT NULL default 'male',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE userjobtypes (
  userid int(11) NOT NULL default '0',
  jobtypeid int(11) NOT NULL default '0',
  PRIMARY KEY  (userid,jobtypeid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE userrule (
  userid int(11) NOT NULL default '0',
  ruleid int(11) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE usersetting (
  id int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (id),
  KEY usersetting (userid,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE voicereply (
  id int(11) NOT NULL auto_increment,
  jobtaskid bigint(20) NOT NULL,
  jobworkitemid bigint(20) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `import` ADD `data` LONGBLOB NOT NULL ;

