-- $rev 1

select 1
$$$

-- $rev 2

ALTER TABLE oauth_access_token DROP INDEX authentication_id, ADD UNIQUE KEY authentication_id (`authentication_id`)
$$$
