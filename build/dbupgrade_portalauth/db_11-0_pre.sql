-- $rev 1

select 1
$$$

-- $rev 2

ALTER TABLE oauth_access_token DROP INDEX authentication_id
$$$

ALTER TABLE oauth_access_token ADD UNIQUE(`authentication_id`)
$$$
