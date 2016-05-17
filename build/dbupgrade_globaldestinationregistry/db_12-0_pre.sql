-- $rev 1
-- no op

-- $rev 2
alter table endpoint
add assumeVerifiedTimestampMs bigint
$$$

update endpoint
set assumeVerifiedTimestampMs = createdTimestampMs
$$$

