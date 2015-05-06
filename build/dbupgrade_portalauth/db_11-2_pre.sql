-- $rev 1

select 1
$$$

-- $rev 2

INSERT INTO
	`oauth_client_details`
SET
	`client_id` = 'raweb-client',
	`client_secret` = 'secret',
	`scope` = 'write,read',
	`authorized_grant_types` = 'implicit',
	`web_server_redirect_uri` = 'https://sandboxinfocenter.testschoolmessenger.com/#/oauthlogin?',
	`authorities` = 'ROLE_CLIENT',
	`additional_information` = '{}',
	`autoapprove` = 'true';
$$$
