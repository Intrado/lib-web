-- $rev 1

do 1
$$$

-- $rev 2

-- unused or redundant indexes per CS-7289

ALTER TABLE loginattempt
  DROP INDEX `status`
$$$

ALTER TABLE server
  DROP INDEX `name`
$$$

