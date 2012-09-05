-- $rev 1

-- need more chars
ALTER TABLE  `importfield` CHANGE  `mapto`  `mapto` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''
$$$

-- all user fieldmaps begin with 'u'
update importfield set mapto = concat('u', mapto) where locate('u', mapto) = 0 and importid in (select id from import where datatype = 'user')
$$$

-- $rev 2
CREATE TABLE IF NOT EXISTS `authenticationprovider` (
  `type` enum('powerschool') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  PRIMARY KEY (`type`,`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 3
CREATE TABLE IF NOT EXISTS `authenticationprovider` (
  `type` enum('powerschool') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  PRIMARY KEY (`type`,`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
$$$

-- $rev 4

-- Add userorganization index to role, index accidentally got added to tai upgrade hence the 
-- create index if if not exists procedure
DROP PROCEDURE IF EXISTS create_index_if_not_exists 
$$$

CREATE DEFINER=`user`@`%` PROCEDURE `create_index_if_not_exists`(table_name_vc varchar(50), index_name_vc varchar(50), field_list_vc varchar(200))
SQL SECURITY INVOKER
BEGIN

set @Index_cnt = (
select count(1) cnt
FROM INFORMATION_SCHEMA.STATISTICS
WHERE table_name = table_name_vc
and index_name = index_name_vc
);

IF ifnull(@Index_cnt,0) = 0 THEN set @index_sql = concat('Alter table ',table_name_vc,' ADD INDEX ',index_name_vc,'(',field_list_vc,');');

PREPARE stmt FROM @index_sql;
EXECUTE stmt;

DEALLOCATE PREPARE stmt;

END IF;

END
$$$

call create_index_if_not_exists('role','userorganization','userid,organizationid')
$$$

-- Drop the temporary stored procedure.
DROP PROCEDURE IF EXISTS create_index_if_not_exists 
$$$