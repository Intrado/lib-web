-- $rev 1

TRUNCATE TABLE loginattempt
$$$

ALTER TABLE loginattempt MODIFY login VARCHAR(255)
$$$


-- $rev 2
ALTER TABLE dbupgradehost MODIFY dbname VARCHAR(64)
$$$

ALTER TABLE dbupgrade MODIFY id VARCHAR(64)
$$$

TRUNCATE TABLE loginattempt
$$$

ALTER TABLE loginattempt MODIFY login VARCHAR(255) COLLATE utf8_general_ci
$$$