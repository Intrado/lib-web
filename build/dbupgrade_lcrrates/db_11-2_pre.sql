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

-- $rev 3

-- unused or redundant indexes per CS-7289

ALTER TABLE qwestrawinterstaterate
  DROP INDEX `lata`
$$$

ALTER TABLE qwesttdmrawinterstaterate
  DROP INDEX `lata`
$$$

ALTER TABLE xorates
  DROP INDEX `npanxx`
$$$

ALTER TABLE xorates
  DROP INDEX `state`
$$$

