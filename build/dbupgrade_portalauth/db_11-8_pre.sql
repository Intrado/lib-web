-- $rev 1

-- no op

-- $rev 2

UPDATE `oauth_client_details`
SET `authorized_grant_types`='password,refresh_token',
  `refresh_token_validity`=86400
WHERE `client_id`='json-client'
$$$
