-- $rev 1
-- no op

-- $rev 2
alter table endpoint
add assumeVerifiedTimestampMs bigint
$$$

update endpoint
set assumeVerifiedTimestampMs = createdTimestampMs
$$$

-- $rev 3
ALTER TABLE `endpoint` ADD INDEX `type_subType_lastIdentifiedTimestamp` (type, subType, lastIdentifiedTimestampMs)
$$$

ALTER TABLE `endpoint` ADD INDEX `type_lastIdentifiedTimestamp` (type, lastIdentifiedTimestampMs)
$$$