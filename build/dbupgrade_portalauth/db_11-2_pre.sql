-- $rev 1

select 1
$$$

-- $rev 2

INSERT IGNORE INTO
	`oauth_client_details`
SET
	`client_id` = 'raweb-client',
	`client_secret` = 'secret',
	`scope` = 'write,read',
	`authorized_grant_types` = 'implicit',
	`web_server_redirect_uri` = 'https://sandboxinfocenter.testschoolmessenger.com/#/oauthlogin?',
	`authorities` = 'ROLE_CLIENT',
	`additional_information` = '{}',
	`autoapprove` = 'true'
$$$

-- $rev 3

-- unused or redundant indexes per CS-7289

ALTER TABLE oauth_access_token
  DROP INDEX `client_id`
$$$
