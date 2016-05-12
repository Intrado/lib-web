-- $rev 1
ALTER TABLE emailevent ADD customerId INT(11) NOT NULL AFTER subType
$$$

-- $rev 2
CREATE  INDEX  messageId ON emailevent (messageId) USING BTREE
$$$

-- $rev 3
alter table emailevent change statusCode statusCode smallint(4) unsigned,
change fromName fromName varchar(100), change fromDomain fromDomain varchar(100),
change toName toName varchar(100), change toDomain toDomain varchar(100),
change responseText responseText text, change rawResponseText rawResponseText text
$$$