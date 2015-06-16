-- $rev 1

do 1
$$$

-- $rev 2

-- The following tables are the last production tables that are in MyISAM format.
ALTER TABLE bandwidthrates ENGINE=InnoDB
$$$
ALTER TABLE defaultrates ENGINE=InnoDB
$$$
ALTER TABLE lerg6 ENGINE=InnoDB
$$$
ALTER TABLE qwestclasslookup ENGINE=InnoDB
$$$
ALTER TABLE qwestrawinterstaterate ENGINE=InnoDB
$$$
ALTER TABLE qwestrawintrastaterate ENGINE=InnoDB
$$$
ALTER TABLE qwesttdmrawinterstaterate ENGINE=InnoDB
$$$
ALTER TABLE qwesttdmrawintrastaterate ENGINE=InnoDB
$$$
ALTER TABLE xorates ENGINE=InnoDB
$$$

-- The following tables exist in some environments, but they're almost a year old.
-- These have been backed up on the production databases.
DROP TABLE IF EXISTS lerg6_archived_on_20140826_1438293600
$$$
DROP TABLE IF EXISTS lerg6_archived_on_20140904_1475893793
$$$
DROP TABLE IF EXISTS lerg6_tmp_20140825_2067188561
$$$
DROP TABLE IF EXISTS lerg6_tmp_20140825_418888255
$$$
DROP TABLE IF EXISTS lerg6_tmp_20140826_1613602649
$$$

