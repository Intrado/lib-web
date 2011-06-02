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

