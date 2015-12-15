-- $rev 1

ALTER TABLE `smsinbound` MODIFY COLUMN `carrier` VARCHAR(100) NOT NULL
$$$

-- $rev 2

-- dummy rev, because this change moved to 11-5 branch.

-- $rev 3

-- update values for Test - Syniverse, which some environments may or may not have inserted
SET @id := (SELECT id FROM `shortcodegroup` WHERE `description` = 'Test - Syniverse')
$$$
INSERT INTO `shortcodegroup` (`id`, `description`, `queuecapacity`, `numthreads`, `product`, `isdefault`)
VALUES (@id, 'Test - Syniverse', 1000, 1, 'cs', '0')
ON DUPLICATE KEY UPDATE `queuecapacity` = VALUES(`queuecapacity`), `numthreads` = VALUES(`numthreads`)
$$$

-- $rev 4

INSERT INTO `shortcodetext` (`shortcode`, `messagetype`, `text`) VALUES
('68453','PENDINGOPTIN','%s messages. Reply Y to confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/tm'),
('88544','PENDINGOPTIN','%s msgs. Reply Y to confirm, HELP 4 info. Msg&data rates may apply. Msg freq varies. schoolmessenger.com/fcs')
ON DUPLICATE KEY UPDATE `text` = VALUES(`text`)
$$$

