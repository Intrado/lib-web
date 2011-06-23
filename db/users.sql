
-- using PHP style variables to note where you need to change a value
-- $some_random_password should be a randomly generated password, different for each user


-- pagelink user for finding codes. used by appserver
CREATE USER 'pagelink_ro'@'%' IDENTIFIED BY '$some_random_password';
GRANT SELECT ON `pagelink`.`$pagedbname` TO 'pagelink_ro'@'%';

-- pagelink user for creating links. used by redialer
CREATE USER 'pagelink_rw'@'%' IDENTIFIED BY '$some_random_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON `pagelink`.`$pagedbname` TO 'pagelink_rw'@'%';

