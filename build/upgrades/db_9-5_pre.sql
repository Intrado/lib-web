-- $rev 1

ALTER TABLE `messagegroup` CHANGE `type` `type` ENUM( 'notification', 'targetedmessage', 'classroomtemplate', 'systemtemplate', 'stationery' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'notification'
$$$

