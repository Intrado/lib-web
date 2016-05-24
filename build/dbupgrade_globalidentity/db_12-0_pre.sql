-- $rev 1
-- no op

-- $rev 2
create table syniversenumberidentity (
id int primary key auto_increment,
address varchar(20),
validity varchar(5),
carrierid varchar(15),
carriername varchar(256),
numbertype varchar(2),
country varchar(256),
portingstatus varchar(5),
mcc varchar(3),
mnc varchar(3),
deactivationdetail varchar(512),
errordesc text,
importStatus enum('NEW', 'SUCCESS','ERROR') not null default 'NEW',
identityTimestampMs bigint not null,
createdTimestampMs bigint not null,
index (address),
index (createdTimestampMs),
index (importStatus)
)
$$$
