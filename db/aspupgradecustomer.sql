-- Upgrade from release 6.1 to 6.2 


create table if not exists customercallstats (
  jobid int(11) NOT NULL,
  userid int(11) NOT NULL,
  finishdate datetime default NULL,
  attempted int(11),
  primary key (jobid)
) engine=innodb
$$$

CREATE TABLE `dmschedule` (
`id` INT NOT NULL auto_increment ,
`dmid` INT NOT NULL ,
`daysofweek` VARCHAR( 20 ) NOT NULL ,
`starttime` TIME NOT NULL ,
`endtime` TIME NOT NULL ,
`resourcepercentage` float NOT NULL DEFAULT '1',
PRIMARY KEY ( `id` )
) ENGINE = innodb

$$$



