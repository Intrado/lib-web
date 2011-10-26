-- $rev 1

-- add post type
ALTER TABLE `message` CHANGE `type` `type` ENUM( 'phone', 'email', 'print', 'sms', 'post' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'phone'
$$$

-- new table for post type messages sent by a job
CREATE TABLE jobpost (
jobid int NOT NULL,
 `type` enum ('facebook','twitter','page') NOT NULL,
destination varchar(255) NOT NULL,
posted tinyint(1) NOT NULL DEFAULT 0,
PRIMARY KEY(jobid, type, destination),
INDEX pagecode(destination)
)
$$$

-- $rev 2

-- add default tiny domain
INSERT ignore INTO `setting` (
`id` ,
`name` ,
`value`
)
VALUES (
NULL , 'tinydomain', 'alrt4.me'
)
$$$

-- remove existing facebook auth tokens
delete from usersetting where name = 'fb_access_token'
$$$

-- $rev 3

-- remove all system messages
delete from systemmessages
$$$

-- insert general new user system message
INSERT INTO systemmessages (message, icon, modifydate)
VALUES (
'<div style="color:#3e693f;font-size: 20px;font-weight: bold;">Welcome New User</div>
  <ul>
  <li>See the Getting Started Guide: <a href="#" onclick="window.open(\'help/index.php\', \'_blank\', \'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes\');"><img src="img/icons/page_white_acrobat.gif" /> Getting Started</a> 
  </ul>', 'largeicons/news.jpg', '2000-01-01 01:02:34'
)
$$$

-- $rev 4

-- add facebook authorize post to wall
INSERT ignore INTO `setting` (
`id` ,
`name` ,
`value`
)
VALUES (
NULL , 'fbauthorizewall', '1'
)
$$$

-- $rev 5

-- fix password to allow NULL
ALTER TABLE `subscriber` CHANGE `password` `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
CHANGE `salt` `salt` VARCHAR( 29 ) CHARACTER SET utf8 COLLATE utf8_bin NULL,
CHANGE `passwordversion` `passwordversion` TINYINT( 4 ) NOT NULL DEFAULT '0'
$$$

-- fix password to allow NULL
ALTER TABLE `user` CHANGE `password` `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
CHANGE `salt` `salt` VARCHAR( 29 ) CHARACTER SET utf8 COLLATE utf8_bin NULL,
CHANGE `passwordversion` `passwordversion` TINYINT( 4 ) NOT NULL DEFAULT '0',
CHANGE `pincode` `pincode` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
$$$


-- $rev 6
ALTER TABLE `job` ADD `activedate` DATETIME default NULL AFTER `modifydate` 
$$$

CREATE TABLE `jobstats` (
 `jobid` int(11) NOT NULL,
 `name` varchar(255) NOT NULL,
 `value` int(11) NOT NULL,
 PRIMARY KEY (`jobid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

