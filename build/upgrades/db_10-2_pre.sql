-- $rev 1

CREATE TABLE `feedcategorytype` (
 `feedcategoryid` int(11) NOT NULL,
 `type` enum('rss','desktop','push') NOT NULL,
 PRIMARY KEY (`feedcategoryid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 2

ALTER TABLE `messagepart` CHANGE `type` `type` ENUM( 'A', 'T', 'V', 'I', 'MAL' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'A',
  ADD `messageattachmentid` INT NULL AFTER `imagecontentid`
$$$

-- $rev 3

ALTER TABLE `bursttemplate` ADD COLUMN `identifierTextPattern` VARCHAR(150);
$$$


