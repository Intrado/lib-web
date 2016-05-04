-- $rev 1
-- no op

-- $rev 2
ALTER TABLE dbupgrade MODIFY id VARCHAR(64)
$$$

-- $rev 3
INSERT INTO `carrierratemodel` (`id`, `name`, `classname`, `params`) VALUES
(26, 'HyperCube CA','HyperCube', '{"state":"ca"}'),
(27, 'HyperCube VA','HyperCube', '{"state":"va"}')
$$$

-- $rev 4
INSERT INTO `carrierratemodel` (`id`, `name`, `classname`, `params`) VALUES
(28, 'HyperCube CA-AWS','HyperCube', '{"state":"ca"}')
$$$
