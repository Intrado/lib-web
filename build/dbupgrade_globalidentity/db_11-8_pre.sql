-- $rev 1
--no op

-- $rev 2
create table identityjob (
id int primary key auto_increment,
name varchar(64) not null,
durationSec int,
status enum('NEW', 'PENDING', 'COMPLETED', 'CANCELLED', 'ERROR'),
details text, -- Job stats or error info (JSON)
createdTimestampMs bigint not null,
index (createdTimestampMs)
)
$$$

create table providerevent (
id int primary key auto_increment,
identityJobId int,
type enum('DEACTIVATION') not null,
providerKey varchar(64) not null,
details mediumtext, -- Provider specific event payload
createdTimestampMs bigint not null,
index (createdTimestampMs),
index (identityJobId)
)
$$$

create table processor (
id int primary key auto_increment,
providerEventId int,
status enum('NEW', 'PENDING', 'COMPLETED', 'ERROR') not null,
details text, -- Processor results/stats (JSON)
createdTimestampMs bigint not null,
index (createdTimestampMs),
index (providerEventId)
)
$$$

create table identityevent (
id int auto_increment,
createdTimestampMs bigint not null,
identityJobId int,
groupKey varchar(64),
destination varchar(255),
type enum('UPDATE_MERGE', 'DEACTIVATION', 'PORT', 'RESET') not null,
details text, -- Event info (JSON)
primary key (id, createdTimestampMs),
index (createdTimestampMs)
) PARTITION BY RANGE (createdTimestampMs)
(
PARTITION pNULL VALUES LESS THAN (0),
PARTITION p20160101 VALUES LESS THAN (UNIX_TIMESTAMP('2016-02-01')*1000),
PARTITION p20160201 VALUES LESS THAN (UNIX_TIMESTAMP('2016-03-01')*1000),
PARTITION p20160301 VALUES LESS THAN (UNIX_TIMESTAMP('2016-04-01')*1000),
PARTITION p20160401 VALUES LESS THAN (UNIX_TIMESTAMP('2016-05-01')*1000),
PARTITION p20160501 VALUES LESS THAN (UNIX_TIMESTAMP('2016-06-01')*1000),
PARTITION p20160601 VALUES LESS THAN (UNIX_TIMESTAMP('2016-07-01')*1000),
PARTITION p20160701 VALUES LESS THAN (UNIX_TIMESTAMP('2016-08-01')*1000),
PARTITION p20160801 VALUES LESS THAN (UNIX_TIMESTAMP('2016-09-01')*1000),
PARTITION p20160901 VALUES LESS THAN (UNIX_TIMESTAMP('2016-10-01')*1000),
PARTITION p20161001 VALUES LESS THAN (UNIX_TIMESTAMP('2016-11-01')*1000),
PARTITION p20161101 VALUES LESS THAN (UNIX_TIMESTAMP('2016-12-01')*1000),
PARTITION p20161201 VALUES LESS THAN (UNIX_TIMESTAMP('2017-01-01')*1000),
PARTITION p20170101 VALUES LESS THAN (UNIX_TIMESTAMP('2017-02-01')*1000),
PARTITION p20170201 VALUES LESS THAN (UNIX_TIMESTAMP('2017-03-01')*1000),
PARTITION p20170301 VALUES LESS THAN (UNIX_TIMESTAMP('2017-04-01')*1000),
PARTITION p20170401 VALUES LESS THAN (UNIX_TIMESTAMP('2017-05-01')*1000),
PARTITION p20170501 VALUES LESS THAN (UNIX_TIMESTAMP('2017-06-01')*1000),
PARTITION p20170601 VALUES LESS THAN (UNIX_TIMESTAMP('2017-07-01')*1000),
PARTITION p20170701 VALUES LESS THAN (UNIX_TIMESTAMP('2017-08-01')*1000),
PARTITION p20170801 VALUES LESS THAN (UNIX_TIMESTAMP('2017-09-01')*1000),
PARTITION p20170901 VALUES LESS THAN (UNIX_TIMESTAMP('2017-10-01')*1000),
PARTITION p20171001 VALUES LESS THAN (UNIX_TIMESTAMP('2017-11-01')*1000),
PARTITION p20171101 VALUES LESS THAN (UNIX_TIMESTAMP('2017-12-01')*1000),
PARTITION p20171201 VALUES LESS THAN (UNIX_TIMESTAMP('2018-01-01')*1000),
PARTITION pMAX VALUES LESS THAN (MAXVALUE)
)
$$$

create table subscriber (
id int primary key auto_increment,
name varchar(64) not null,
notificationUrl varchar(2048) not null,
pageSize int,
lastIdentityEventId int,
createdTimestampMs bigint not null,
status enum('ACTIVE','INACTIVE') not null
)
$$$

create table subscription (
subscriberId int not null,
type enum('UPDATE_MERGE', 'DEACTIVATION', 'PORT', 'RESET') not null
)
$$$