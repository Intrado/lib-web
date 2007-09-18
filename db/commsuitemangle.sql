--
-- Mangle queries to convert old db to new reporting db
--

-- queries that might not be in the commsuite

-- field to keep track of how much a jobs workitems priority has been adjusted
ALTER TABLE `job` ADD `priorityadjust` INT NOT NULL DEFAULT '0' AFTER `ranautoreport` 
$$$


-- configurable bucket/timeslice size
ALTER TABLE `jobtype` ADD `timeslices` SMALLINT NOT NULL DEFAULT '0' AFTER `systempriority` 
$$$

update jobtype set timeslices = 50 where systempriority=1
$$$
update jobtype set timeslices = 0 where systempriority=2
$$$
update jobtype set timeslices = 100 where systempriority=3
$$$

-- mangle old data to new data
drop table reportcompleted
$$$


ALTER TABLE `person`
ADD `f01` VARCHAR( 50 ) NOT NULL ,
ADD `f02` VARCHAR( 50 ) NOT NULL ,
ADD `f03` VARCHAR( 50 ) NOT NULL ,
ADD `f04` VARCHAR( 255 ) NOT NULL ,
ADD `f05` VARCHAR( 255 ) NOT NULL ,
ADD `f06` VARCHAR( 255 ) NOT NULL ,
ADD `f07` VARCHAR( 255 ) NOT NULL ,
ADD `f08` VARCHAR( 255 ) NOT NULL ,
ADD `f09` VARCHAR( 255 ) NOT NULL ,
ADD `f10` VARCHAR( 255 ) NOT NULL ,
ADD `f11` VARCHAR( 255 ) NOT NULL ,
ADD `f12` VARCHAR( 255 ) NOT NULL ,
ADD `f13` VARCHAR( 255 ) NOT NULL ,
ADD `f14` VARCHAR( 255 ) NOT NULL ,
ADD `f15` VARCHAR( 255 ) NOT NULL ,
ADD `f16` VARCHAR( 255 ) NOT NULL ,
ADD `f17` VARCHAR( 255 ) NOT NULL ,
ADD `f18` VARCHAR( 255 ) NOT NULL ,
ADD `f19` VARCHAR( 255 ) NOT NULL ,
ADD `f20` VARCHAR( 255 ) NOT NULL 
$$$


update person p inner join persondata pd on (pd.personid=p.id) set
p.f01 = pd.f01,
p.f02 = pd.f02,
p.f03 = pd.f03,
p.f04 = pd.f04,
p.f05 = pd.f05,
p.f06 = pd.f06,
p.f07 = pd.f07,
p.f08 = pd.f08,
p.f09 = pd.f09,
p.f10 = pd.f10,
p.f11 = pd.f11,
p.f12 = pd.f12,
p.f13 = pd.f13,
p.f14 = pd.f14,
p.f15 = pd.f15,
p.f16 = pd.f16,
p.f17 = pd.f17,
p.f18 = pd.f18,
p.f19 = pd.f19,
p.f20 = pd.f20
$$$

CREATE TABLE `reportperson` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  `userid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  `messageid` int(11) NOT NULL,
  `status` enum('new','queued','assigned','fail','success','duplicate','blocked') NOT NULL,
  `numcontacts` tinyint(4) NOT NULL,
  `numduperemoved` tinyint(4) NOT NULL,
  `numblocked` tinyint(4) NOT NULL,
  `pkey` varchar(255) default NULL,
  `f01` varchar(50) NOT NULL default '',
  `f02` varchar(50) NOT NULL default '',
  `f03` varchar(50) NOT NULL default '',
  `f04` varchar(255) NOT NULL default '',
  `f05` varchar(255) NOT NULL default '',
  `f06` varchar(255) NOT NULL default '',
  `f07` varchar(255) NOT NULL default '',
  `f08` varchar(255) NOT NULL default '',
  `f09` varchar(255) NOT NULL default '',
  `f10` varchar(255) NOT NULL default '',
  `f11` varchar(255) NOT NULL default '',
  `f12` varchar(255) NOT NULL default '',
  `f13` varchar(255) NOT NULL default '',
  `f14` varchar(255) NOT NULL default '',
  `f15` varchar(255) NOT NULL default '',
  `f16` varchar(255) NOT NULL default '',
  `f17` varchar(255) NOT NULL default '',
  `f18` varchar(255) NOT NULL default '',
  `f19` varchar(255) NOT NULL default '',
  `f20` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`jobid`,`type`,`personid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

CREATE TABLE `reportcontact` (
  `jobid` int(11) NOT NULL,
  `personid` int(11) NOT NULL,
  `type` enum('phone','email','print') NOT NULL,
  `sequence` tinyint(4) NOT NULL,
  `numattempts` tinyint(4) NOT NULL,
  `userid` int(11) NOT NULL,
  `customerid` int(11) NOT NULL,
  `starttime` bigint(20) NOT NULL default '0',
  `result` enum('C','A','M','N','B','X','F','sent','unsent','printed','notprinted') NOT NULL,
  `participated` tinyint(4) NOT NULL default '0',
  `duration` float default NULL,
  `resultdata` text,
  attemptdata varchar(200),
  `phone` varchar(20) default NULL,
  `email` varchar(100) default NULL,
  `addressee` varchar(50) default NULL,
  `addr1` varchar(50) default NULL,
  `addr2` varchar(50) default NULL,
  `city` varchar(50) default NULL,
  `state` char(2) default NULL,
  `zip` varchar(10) default NULL,
  PRIMARY KEY  (`jobid`,`type`,`personid`,`sequence`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$


-- clean up records with duplicate junk in workitems

delete wi2 from
jobworkitem wi1 left join jobworkitem wi2 on (wi2.id > wi1.id and wi2.jobid = wi1.jobid and wi2.type = wi1.type and wi1.personid=wi2.personid)
where wi2.id is not null
$$$

-- report person

insert into reportperson
select

j.id as jobid,
p.id as personid,
wi.type as type,
j.userid as userid,
u.customerid as customerid,
wi.messageid as messageid,
wi.status as status,
(select count(*) from jobtask jt where jt.jobworkitemid=wi.id) as numcontacts,
0 as numduperemoved,
0 as numblocked,

p.pkey as pkey,
p.f01 as f01,
p.f02 as f02,
p.f03 as f03,
p.f04 as f04,
p.f05 as f05,
p.f06 as f06,
p.f07 as f07,
p.f08 as f08,
p.f09 as f09,
p.f10 as f10,
p.f11 as f11,
p.f12 as f12,
p.f13 as f13,
p.f14 as f14,
p.f15 as f15,
p.f16 as f16,
p.f17 as f17,
p.f18 as f18,
p.f19 as f19,
p.f20 as f20

from person p
inner join jobworkitem wi
	on (p.id = wi.personid)
inner join job j
	on (wi.jobid = j.id)
inner join user u
	on (u.id = j.userid)
left join message m on
	(m.id = wi.messageid)
	$$$



-- reportattempt

-- clean up records with bad sequences

delete jt2 from
jobtask jt1 left join jobtask jt2 on (jt2.id > jt1.id and jt2.jobworkitemid = jt1.jobworkitemid and jt2.sequence = jt1.sequence)
where jt2.id is not null
$$$

-- insert phones
insert into reportcontact

select

j.id as jobid,
wi.personid as personid,
wi.type as type,
jt.sequence as sequence,
jt.numattempts as numattempts,
j.userid as userid,
u.customerid as customerid,

ifnull(cl.starttime,jt.lastattempt) as starttime,

case wi.type
 when 'phone' then cl.callprogress
 when 'email' then if (wi.status='success','sent','unsent')
 when 'print' then if (wi.status='success','printed','notprinted')
 end as result,
ifnull(cl.participated,0) as participated,
cl.duration as duration,
coalesce(cl.resultdata, sec.resultdata) as resultdata,

(select group_concat(cl2.starttime,':',cl2.callprogress order by cl2.callattempt)
 from calllog cl2 where cl2.jobtaskid = jt.id) as attemptdata,

jt.phone as phone,
jt.email as email,
a.addressee as addressee,
a.addr1 as addr1,
a.addr2 as addr2,
a.city as city,
a.state as state,
a.zip as zip


from jobworkitem wi
inner join job j on (wi.jobid = j.id)
inner join user u on (u.id = j.userid)
inner join jobtask jt on
(jt.jobworkitemid=wi.id)
inner join calllog cl on
(cl.jobtaskid=jt.id and cl.callattempt = jt.numattempts-1)
left join address a on
(a.id=jt.addressid and wi.type='print')
left join surveyemailcode sec on
(sec.jobworkitemid = wi.id and j.type='survey' and wi.type='email')
where wi.type = 'phone'
$$$

-- insert emails
insert into reportcontact

select

j.id as jobid,
wi.personid as personid,
wi.type as type,
jt.sequence as sequence,
jt.numattempts as numattempts,
j.userid as userid,
u.customerid as customerid,

ifnull(cl.starttime,jt.lastattempt) as starttime,

case wi.type
 when 'phone' then cl.callprogress
 when 'email' then if (wi.status='success','sent','unsent')
 when 'print' then if (wi.status='success','printed','notprinted')
 end as result,
ifnull(cl.participated,0) as participated,
cl.duration as duration,
coalesce(cl.resultdata, sec.resultdata) as resultdata,

(select group_concat(cl2.starttime,':',cl2.callprogress order by cl2.callattempt)
 from calllog cl2 where cl2.jobtaskid = jt.id) as attemptdata,

jt.phone as phone,
jt.email as email,
a.addressee as addressee,
a.addr1 as addr1,
a.addr2 as addr2,
a.city as city,
a.state as state,
a.zip as zip


from jobworkitem wi
inner join job j on (wi.jobid = j.id)
inner join user u on (u.id = j.userid)
inner join jobtask jt on
(jt.jobworkitemid=wi.id)
left join calllog cl on
(cl.jobtaskid=jt.id and cl.callattempt = jt.numattempts-1)
left join address a on
(a.id=jt.addressid and wi.type='print')
left join surveyemailcode sec on
(sec.jobworkitemid = wi.id and j.type='survey' and wi.type='email')
where wi.type = 'email'
$$$

-- insert prints
insert into reportcontact

select

j.id as jobid,
wi.personid as personid,
wi.type as type,
jt.sequence as sequence,
jt.numattempts as numattempts,
j.userid as userid,
u.customerid as customerid,

ifnull(cl.starttime,jt.lastattempt) as starttime,

case wi.type
 when 'phone' then cl.callprogress
 when 'email' then if (wi.status='success','sent','unsent')
 when 'print' then if (wi.status='success','printed','notprinted')
 end as result,
ifnull(cl.participated,0) as participated,
cl.duration as duration,
coalesce(cl.resultdata, sec.resultdata) as resultdata,

(select group_concat(cl2.starttime,':',cl2.callprogress order by cl2.callattempt)
 from calllog cl2 where cl2.jobtaskid = jt.id) as attemptdata,

jt.phone as phone,
jt.email as email,
a.addressee as addressee,
a.addr1 as addr1,
a.addr2 as addr2,
a.city as city,
a.state as state,
a.zip as zip


from jobworkitem wi
inner join job j on (wi.jobid = j.id)
inner join user u on (u.id = j.userid)
inner join jobtask jt on
(jt.jobworkitemid=wi.id)
left join calllog cl on
(cl.jobtaskid=jt.id and cl.callattempt = jt.numattempts-1)
left join address a on
(a.id=jt.addressid and wi.type='print')
left join surveyemailcode sec on
(sec.jobworkitemid = wi.id and j.type='survey' and wi.type='email')
where wi.type = 'print'
$$$


drop table persondata$$$

ALTER TABLE `import` ADD `data` LONGBLOB Default NULL 
$$$

ALTER TABLE `import` ADD `datamodifiedtime` DATETIME NULL 
$$$

CREATE TABLE `surveyweb` (
`code` CHAR( 22 ) CHARACTER SET ascii COLLATE ascii_bin NOT NULL ,
`jobid` INT( 11 ) NOT NULL ,
`personid` INT( 11 ) NOT NULL ,
`customerid` INT( 11 ) NOT NULL ,
`status` ENUM( 'noresponse', 'web', 'phone' ) NOT NULL ,
`dateused` DATETIME NULL ,
`loggedip` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_bin NULL ,
`resultdata` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8 
$$$


insert into surveyweb
select sec.code, wi.jobid,
wi.personid, wi.customerid,
sec.isused+1, sec.dateused,
sec.loggedip, sec.resultdata
from surveyemailcode sec
inner join jobworkitem wi on (wi.id = sec.jobworkitemid)
$$$


-- set fieldmap options from hardcoded

update fieldmap fm set fm.options=concat(fm.options, ',firstname') where fm.fieldnum = 'f01'
$$$
update fieldmap fm set fm.options=concat(fm.options, ',lastname') where fm.fieldnum = 'f02'
$$$
update fieldmap fm set fm.options=concat(fm.options, ',language') where fm.fieldnum = 'f03'
$$$

-- fix jobtype priorities

update jobtype set systempriority=priority/10000
$$$
update jobtype set systempriority=3 where name like '%General%'
$$$
update jobtype set systempriority=3 where systempriority > 3
$$$

select count(*), name, systempriority, priority from jobtype where not deleted group by name, systempriority, priority
$$$
